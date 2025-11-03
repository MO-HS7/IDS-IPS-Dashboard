<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNetworkLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\NetworkLog::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:' . implode(',', config('ids.file_upload.allowed_mimes', ['csv', 'txt', 'pcap', 'pcapng', 'cap'])),
                'max:' . config('ids.file_upload.max_size', 512000), // 500MB for PCAP files
                function ($attribute, $value, $fail) {
                    $extension = strtolower($value->getClientOriginalExtension());
                    
                    // For PCAP files, skip MIME type checking (they can have various MIME types)
                    if (in_array($extension, ['pcap', 'pcapng', 'cap'])) {
                        // Enhanced PCAP validation
                        $handle = fopen($value->getPathname(), 'rb');
                        if (!$handle) {
                            $fail('Unable to read PCAP file.');
                            return;
                        }
                        
                        // Read first 24 bytes for full PCAP header validation
                        $header = fread($handle, 24);
                        $fileSize = filesize($value->getPathname());
                        fclose($handle);
                        
                        if (strlen($header) < 24) {
                            $fail('PCAP file is too small or corrupted.');
                            return;
                        }
                        
                        $magicBytes = substr($header, 0, 4);
                        $isValid = false;
                        $isPcapNg = false;
                        
                        // Validate magic bytes and file structure
                        if ($magicBytes === "\xd4\xc3\xb2\xa1" || $magicBytes === "\xa1\xb2\xc3\xd4") {
                            // Standard PCAP format
                            $isValid = true;
                            
                            // Validate version (bytes 4-5: major version, bytes 6-7: minor version)
                            // Typical versions are 2.4
                            $majorVersion = unpack('v', substr($header, 4, 2))[1];
                            $minorVersion = unpack('v', substr($header, 6, 2))[1];
                            
                            if ($majorVersion > 10 || $minorVersion > 10) {
                                $fail('PCAP file has an invalid version.');
                                return;
                            }
                            
                        } elseif ($magicBytes === "\x0a\x0d\x0d\x0a") {
                            // PCAPNG format
                            $isValid = true;
                            $isPcapNg = true;
                            
                            // PCAPNG has a Section Header Block after magic bytes
                            // Validate that the next 4 bytes contain a valid block type
                            $blockType = unpack('V', substr($header, 4, 4))[1];
                            if ($blockType !== 0x0A0D0D0A) {
                                $fail('Invalid PCAPNG file structure.');
                                return;
                            }
                        }
                        
                        if (!$isValid) {
                            $fail('Invalid PCAP file format. File must have valid PCAP or PCAPNG magic bytes.');
                            return;
                        }
                        
                        // Additional security checks
                        // 1. Check minimum file size (header + at least one small packet)
                        $minSize = $isPcapNg ? 28 : 40; // Minimum viable PCAP/PCAPNG file
                        if ($fileSize < $minSize) {
                            $fail('PCAP file is too small to contain valid packet data.');
                            return;
                        }
                        
                        // 2. Check maximum file size (prevent DoS)
                        $maxPcapSize = config('ids.file_upload.max_pcap_size', 524288000); // 500MB default
                        if ($fileSize > $maxPcapSize) {
                            $fail('PCAP file exceeds maximum allowed size.');
                            return;
                        }
                        
                        // 3. Check for null bytes beyond header (basic corruption check)
                        $handle = fopen($value->getPathname(), 'rb');
                        fseek($handle, 24);
                        $sample = fread($handle, 100);
                        fclose($handle);
                        
                        // PCAP files should have varied byte content, not all nulls
                        if (strlen(str_replace("\x00", '', $sample)) < 10) {
                            $fail('PCAP file appears to be corrupted or empty.');
                            return;
                        }
                        
                        return; // Skip other checks for PCAP files
                    }
                    
                    // For CSV/TXT files, perform security checks
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $value->getPathname());
                    finfo_close($finfo);
                    
                    $allowedMimes = config('ids.file_upload.allowed_mime_types', [
                        'text/csv', 
                        'text/plain', 
                        'application/csv',
                        'application/octet-stream'
                    ]);
                    
                    if (!in_array($mimeType, $allowedMimes)) {
                        $fail('Invalid file type detected. Expected CSV or TXT file.');
                    }
                    
                    // Check file size for CSV files (smaller limit than PCAP)
                    $maxCsvSize = config('ids.file_upload.max_csv_size', 52428800); // 50MB default
                    if ($value->getSize() > $maxCsvSize) {
                        $fail('CSV file exceeds maximum allowed size.');
                    }
                    
                    // Check for suspicious file content
                    $content = file_get_contents($value->getPathname(), false, null, 0, config('ids.security.max_file_scan_size', 2048));
                    
                    // Expanded suspicious patterns
                    $suspiciousPatterns = config('ids.security.suspicious_content_patterns', [
                        '<?php', 
                        '<script', 
                        'eval(',
                        'base64_decode',
                        'system(',
                        'exec(',
                        'shell_exec',
                        'passthru',
                        'proc_open',
                        '__import__',
                        'import os',
                        'import subprocess'
                    ]);
                    
                    foreach ($suspiciousPatterns as $pattern) {
                        if (stripos($content, $pattern) !== false) {
                            $fail('File contains potentially malicious content.');
                            break;
                        }
                    }
                    
                    // Validate CSV structure (basic check)
                    if ($extension === 'csv') {
                        $lines = explode("\n", $content);
                        if (count($lines) < 2) {
                            $fail('CSV file must contain at least a header row and one data row.');
                        }
                    }
                }
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file is not valid.',
            'file.mimes' => 'The file must be a CSV, TXT, PCAP, or PCAPNG file.',
            'file.max' => 'The file size must not exceed 500MB.',
        ];
    }
}
