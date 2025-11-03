#!/usr/bin/env python3
"""
PCAP File Analyzer
Extracts metadata and analyzes packets from PCAP/PCAPNG files
"""

import argparse
import sys
import json
import os
from datetime import datetime
from typing import Dict, List, Any, Optional

try:
    from scapy.all import rdpcap, PcapReader, IP, TCP, UDP, ICMP, ARP, Raw
    from scapy.layers.inet import _IPOption_HDR
except ImportError:
    print(json.dumps({"error": "Scapy is not installed. Install with: pip install scapy"}))
    sys.exit(1)

import requests


class PcapAnalyzer:
    """Analyzes PCAP files and extracts packet information"""
    
    def __init__(self, file_path: str):
        self.file_path = file_path
        self.packets = []
        self.metadata = {
            'packet_count': 0,
            'start_time': None,
            'end_time': None,
            'duration': 0,
            'protocols': {},
            'ip_addresses': {'source': set(), 'destination': set()},
            'ports': {'source': set(), 'destination': set()}
        }
    
    def extract_metadata(self) -> Dict[str, Any]:
        """Extract basic metadata from PCAP file without loading all packets"""
        try:
            print(f"Extracting metadata from: {self.file_path}", file=sys.stderr)
            
            # Check file exists
            if not os.path.exists(self.file_path):
                return {"error": "File not found"}
            
            # Get file size
            file_size = os.path.getsize(self.file_path)
            self.metadata['file_size'] = file_size
            
            # Read packets in streaming mode to get metadata
            packet_count = 0
            first_timestamp = None
            last_timestamp = None
            protocols = {}
            
            with PcapReader(self.file_path) as pcap_reader:
                for packet in pcap_reader:
                    packet_count += 1
                    
                    # Get timestamp
                    if hasattr(packet, 'time'):
                        timestamp = float(packet.time)
                        if first_timestamp is None:
                            first_timestamp = timestamp
                        last_timestamp = timestamp
                    
                    # Count protocols
                    if packet.haslayer(TCP):
                        protocols['TCP'] = protocols.get('TCP', 0) + 1
                    elif packet.haslayer(UDP):
                        protocols['UDP'] = protocols.get('UDP', 0) + 1
                    elif packet.haslayer(ICMP):
                        protocols['ICMP'] = protocols.get('ICMP', 0) + 1
                    elif packet.haslayer(ARP):
                        protocols['ARP'] = protocols.get('ARP', 0) + 1
                    else:
                        protocols['Other'] = protocols.get('Other', 0) + 1
                    
                    # Limit metadata extraction for large files
                    if packet_count >= 10000:
                        # Estimate total packets based on file size
                        avg_packet_size = file_size / packet_count
                        estimated_total = int(file_size / avg_packet_size)
                        packet_count = estimated_total
                        break
            
            # Calculate duration
            duration = 0
            if first_timestamp and last_timestamp:
                duration = int(last_timestamp - first_timestamp)
                self.metadata['start_time'] = datetime.fromtimestamp(first_timestamp).isoformat()
                self.metadata['end_time'] = datetime.fromtimestamp(last_timestamp).isoformat()
            
            self.metadata['packet_count'] = packet_count
            self.metadata['duration'] = duration
            self.metadata['protocols'] = protocols
            
            return self.metadata
            
        except Exception as e:
            return {"error": str(e)}
    
    def get_quick_summary(self) -> Dict[str, Any]:
        """Get a quick summary without full analysis"""
        metadata = self.extract_metadata()
        
        if 'error' in metadata:
            return metadata
        
        summary = {
            'file_name': os.path.basename(self.file_path),
            'file_size': metadata.get('file_size', 0),
            'packet_count': metadata.get('packet_count', 0),
            'duration': metadata.get('duration', 0),
            'start_time': metadata.get('start_time'),
            'end_time': metadata.get('end_time'),
            'protocols': metadata.get('protocols', {}),
            'estimated_processing_time': self._estimate_processing_time(metadata.get('packet_count', 0))
        }
        
        return summary
    
    def _estimate_processing_time(self, packet_count: int) -> str:
        """Estimate processing time based on packet count"""
        # Rough estimate: 1000 packets per second
        seconds = packet_count / 1000
        
        if seconds < 60:
            return f"{int(seconds)} seconds"
        elif seconds < 3600:
            return f"{int(seconds / 60)} minutes"
        else:
            return f"{int(seconds / 3600)} hours"
    
    def analyze_packet(self, packet) -> Optional[Dict[str, Any]]:
        """Extract information from a single packet"""
        try:
            packet_data = {
                'timestamp': float(packet.time) if hasattr(packet, 'time') else None,
                'length': len(packet),
                'source_ip': None,
                'destination_ip': None,
                'source_port': None,
                'destination_port': None,
                'protocol': 'Unknown',
                'flags': None,
                'payload_size': 0,
                'info': ''
            }
            
            # IP Layer
            if packet.haslayer(IP):
                ip_layer = packet[IP]
                packet_data['source_ip'] = ip_layer.src
                packet_data['destination_ip'] = ip_layer.dst
                packet_data['protocol'] = ip_layer.proto
                
                # TCP Layer
                if packet.haslayer(TCP):
                    tcp_layer = packet[TCP]
                    packet_data['source_port'] = tcp_layer.sport
                    packet_data['destination_port'] = tcp_layer.dport
                    packet_data['protocol'] = 'TCP'
                    packet_data['flags'] = str(tcp_layer.flags)
                    
                    # Check for common ports
                    if tcp_layer.dport in [80, 8080]:
                        packet_data['info'] = 'HTTP'
                    elif tcp_layer.dport == 443:
                        packet_data['info'] = 'HTTPS'
                    elif tcp_layer.dport == 22:
                        packet_data['info'] = 'SSH'
                    elif tcp_layer.dport == 21:
                        packet_data['info'] = 'FTP'
                
                # UDP Layer
                elif packet.haslayer(UDP):
                    udp_layer = packet[UDP]
                    packet_data['source_port'] = udp_layer.sport
                    packet_data['destination_port'] = udp_layer.dport
                    packet_data['protocol'] = 'UDP'
                    
                    if udp_layer.dport == 53:
                        packet_data['info'] = 'DNS'
                    elif udp_layer.dport in [67, 68]:
                        packet_data['info'] = 'DHCP'
                
                # ICMP Layer
                elif packet.haslayer(ICMP):
                    packet_data['protocol'] = 'ICMP'
                    packet_data['info'] = 'ICMP'
            
            # ARP Layer
            elif packet.haslayer(ARP):
                arp_layer = packet[ARP]
                packet_data['source_ip'] = arp_layer.psrc
                packet_data['destination_ip'] = arp_layer.pdst
                packet_data['protocol'] = 'ARP'
                packet_data['info'] = 'ARP'
            
            # Payload size
            if packet.haslayer(Raw):
                packet_data['payload_size'] = len(packet[Raw].load)
            
            return packet_data
            
        except Exception as e:
            print(f"Error analyzing packet: {e}", file=sys.stderr)
            return None
    
    def analyze_and_send(self, network_log_id: int, api_url: str):
        """Analyze PCAP and send packets to API"""
        try:
            print(f"Analyzing PCAP file: {self.file_path}", file=sys.stderr)
            
            packet_count = 0
            batch = []
            batch_size = 50
            
            with PcapReader(self.file_path) as pcap_reader:
                for packet in pcap_reader:
                    packet_count += 1
                    
                    packet_data = self.analyze_packet(packet)
                    if packet_data:
                        batch.append(packet_data)
                    
                    # Send batch when full
                    if len(batch) >= batch_size:
                        self._send_batch_to_api(network_log_id, batch, api_url)
                        batch = []
                    
                    # Progress update every 1000 packets
                    if packet_count % 1000 == 0:
                        print(f"Processed {packet_count} packets...", file=sys.stderr)
            
            # Send remaining packets
            if batch:
                self._send_batch_to_api(network_log_id, batch, api_url)
            
            print(f"Analysis complete. Total packets: {packet_count}", file=sys.stderr)
            return {'success': True, 'packets_processed': packet_count}
            
        except Exception as e:
            print(f"Error during analysis: {e}", file=sys.stderr)
            return {'success': False, 'error': str(e)}
    
    def _send_batch_to_api(self, network_log_id: int, packets: List[Dict], api_url: str):
        """Send batch of packets to API"""
        try:
            response = requests.post(
                api_url,
                json={
                    'network_log_id': network_log_id,
                    'packets': packets
                },
                timeout=30
            )
            
            if response.status_code != 200:
                print(f"API error: {response.status_code}", file=sys.stderr)
        
        except Exception as e:
            print(f"Failed to send batch to API: {e}", file=sys.stderr)


def main():
    """Main entry point"""
    parser = argparse.ArgumentParser(description='PCAP File Analyzer')
    parser.add_argument('--file', required=True, help='Path to PCAP file')
    parser.add_argument('--action', required=True, choices=['metadata', 'summary', 'analyze'],
                        help='Action to perform')
    parser.add_argument('--network-log-id', type=int, help='Network log ID (for analyze action)')
    parser.add_argument('--api-url', help='API URL to send packets (for analyze action)')
    
    args = parser.parse_args()
    
    analyzer = PcapAnalyzer(args.file)
    
    if args.action == 'metadata':
        metadata = analyzer.extract_metadata()
        print(json.dumps(metadata))
    
    elif args.action == 'summary':
        summary = analyzer.get_quick_summary()
        print(json.dumps(summary))
    
    elif args.action == 'analyze':
        if not args.network_log_id or not args.api_url:
            print(json.dumps({"error": "network-log-id and api-url required for analyze action"}))
            sys.exit(1)
        
        result = analyzer.analyze_and_send(args.network_log_id, args.api_url)
        print(json.dumps(result))
    
    else:
        print(json.dumps({"error": "Invalid action"}))
        sys.exit(1)


if __name__ == '__main__':
    main()
