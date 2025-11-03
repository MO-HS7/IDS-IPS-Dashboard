<?php

namespace App\Services;

use App\Models\NetworkLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class PcapAnalysisService
{
    /**
     * Validate PCAP file
     */
    public function validatePcapFile(string $filePath): array
    {
        $result = [
            'valid' => false,
            'message' => '',
            'metadata' => []
        ];

        // Check file exists
        if (!file_exists($filePath)) {
            $result['message'] = 'File not found';
            return $result;
        }

        // Check file size (max 500MB)
        $fileSize = filesize($filePath);
        if ($fileSize > 500 * 1024 * 1024) {
            $result['message'] = 'File too large (max 500MB)';
            return $result;
        }

        // Check file extension
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($extension, ['pcap', 'pcapng', 'cap'])) {
            $result['message'] = 'Invalid file extension. Must be .pcap, .pcapng, or .cap';
            return $result;
        }

        // Read file header to verify it's a valid PCAP file
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            $result['message'] = 'Cannot read file';
            return $result;
        }

        $header = fread($handle, 4);
        fclose($handle);

        // Check for PCAP magic numbers
        $magicNumbers = [
            "\xd4\xc3\xb2\xa1", // PCAP (little-endian)
            "\xa1\xb2\xc3\xd4", // PCAP (big-endian)
            "\x0a\x0d\x0d\x0a", // PCAPNG
        ];

        $isValid = false;
        foreach ($magicNumbers as $magic) {
            if (substr($header, 0, strlen($magic)) === $magic) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            $result['message'] = 'Invalid PCAP file format';
            return $result;
        }

        $result['valid'] = true;
        $result['message'] = 'Valid PCAP file';
        $result['metadata'] = [
            'file_size' => $fileSize,
            'file_type' => $extension === 'pcapng' ? 'pcapng' : 'pcap'
        ];

        return $result;
    }

    /**
     * Extract metadata from PCAP file using Python
     */
    public function extractMetadata(string $filePath): array
    {
        try {
            $pythonScript = base_path('ml_scripts/pcap_analyzer.py');
            
            if (!file_exists($pythonScript)) {
                throw new \Exception('PCAP analyzer script not found');
            }

            $pythonPath = $this->getPythonPath();
            
            $command = sprintf(
                '%s "%s" --file "%s" --action metadata',
                $pythonPath,
                $pythonScript,
                $filePath
            );

            $result = Process::timeout(60)->run($command);

            if (!$result->successful()) {
                throw new \Exception('Metadata extraction failed: ' . $result->errorOutput());
            }

            $output = $result->output();
            $metadata = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON output from analyzer');
            }

            return $metadata;

        } catch (\Exception $e) {
            Log::error('PCAP metadata extraction failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);

            return [
                'packet_count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process PCAP file and extract packets
     */
    public function processPcapFile(NetworkLog $networkLog, callable $progressCallback = null): bool
    {
        try {
            $filePath = Storage::disk('public')->path($networkLog->file_path);
            
            // Validate file
            $validation = $this->validatePcapFile($filePath);
            if (!$validation['valid']) {
                $networkLog->markAsFailed($validation['message']);
                return false;
            }

            // Extract metadata
            if ($progressCallback) {
                $progressCallback(10, 'Extracting file metadata...');
            }
            
            $metadata = $this->extractMetadata($filePath);
            
            if (isset($metadata['error'])) {
                $networkLog->markAsFailed($metadata['error']);
                return false;
            }

            // Update network log with metadata
            $networkLog->update([
                'file_size' => $validation['metadata']['file_size'],
                'file_type' => $validation['metadata']['file_type'],
                'packet_count' => $metadata['packet_count'] ?? 0,
                'capture_start_time' => $metadata['start_time'] ?? null,
                'capture_end_time' => $metadata['end_time'] ?? null,
                'capture_duration' => $metadata['duration'] ?? null,
                'file_metadata' => $metadata,
                'processing_progress' => 20
            ]);

            if ($progressCallback) {
                $progressCallback(20, 'Processing packets...');
            }

            // Process packets using Python script
            $success = $this->analyzePcapPackets($networkLog, $progressCallback);

            if ($success) {
                $networkLog->markAsCompleted();
                if ($progressCallback) {
                    $progressCallback(100, 'Processing completed');
                }
            }

            return $success;

        } catch (\Exception $e) {
            Log::error('PCAP processing failed', [
                'network_log_id' => $networkLog->id,
                'error' => $e->getMessage()
            ]);

            $networkLog->markAsFailed($e->getMessage());
            return false;
        }
    }

    /**
     * Analyze PCAP packets and create alerts
     */
    protected function analyzePcapPackets(NetworkLog $networkLog, callable $progressCallback = null): bool
    {
        try {
            $pythonScript = base_path('ml_scripts/pcap_analyzer.py');
            $filePath = Storage::disk('public')->path($networkLog->file_path);
            $pythonPath = $this->getPythonPath();
            
            $command = sprintf(
                '%s "%s" --file "%s" --action analyze --network-log-id %d --api-url "%s"',
                $pythonPath,
                $pythonScript,
                $filePath,
                $networkLog->id,
                config('app.url') . '/api/pcap/process-packet'
            );

            $result = Process::timeout(600)->run($command);

            if (!$result->successful()) {
                throw new \Exception('Packet analysis failed: ' . $result->errorOutput());
            }

            return true;

        } catch (\Exception $e) {
            Log::error('PCAP packet analysis failed', [
                'network_log_id' => $networkLog->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get Python executable path
     */
    protected function getPythonPath(): string
    {
        $venvPath = base_path('ml_scripts/venv');
        
        if (PHP_OS_FAMILY === 'Windows') {
            $pythonExe = $venvPath . '/Scripts/python.exe';
            if (file_exists($pythonExe)) {
                return $pythonExe;
            }
            return 'python';
        } else {
            $pythonExe = $venvPath . '/bin/python';
            if (file_exists($pythonExe)) {
                return $pythonExe;
            }
            return 'python3';
        }
    }

    /**
     * Get quick summary of PCAP file without full processing
     */
    public function getQuickSummary(string $filePath): array
    {
        try {
            $pythonScript = base_path('ml_scripts/pcap_analyzer.py');
            $pythonPath = $this->getPythonPath();
            
            $command = sprintf(
                '%s "%s" --file "%s" --action summary',
                $pythonPath,
                $pythonScript,
                $filePath
            );

            $result = Process::timeout(30)->run($command);

            if (!$result->successful()) {
                return ['error' => 'Failed to generate summary'];
            }

            $output = $result->output();
            $summary = json_decode($output, true);

            return $summary ?? ['error' => 'Invalid response'];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
