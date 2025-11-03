#!/usr/bin/env python3
"""
Live Network Packet Capture Script
Captures network packets in real-time using Scapy and sends them to Laravel backend
"""

import argparse
import sys
import json
import time
import threading
import signal
from datetime import datetime
from typing import Dict, Any, Optional
from urllib import request as url_request
from urllib.error import URLError

try:
    from scapy.all import sniff, IP, TCP, UDP, ICMP, ARP, Raw, conf
    from scapy.layers.inet import _IPOption_HDR
except ImportError:
    print("Error: Scapy is not installed. Please install it using: pip install scapy")
    sys.exit(1)

try:
    import redis
    REDIS_AVAILABLE = True
except ImportError:
    REDIS_AVAILABLE = False
    print("Warning: Redis is not installed. Falling back to webhook API.")
    print("Install Redis with: pip install redis")


class LivePacketCapture:
    """Captures network packets and sends them to the API"""
    
    def __init__(self, session_id: str, interface: str, api_url: str = None, api_token: str = None, 
                 redis_host: str = 'localhost', redis_port: int = 6379, redis_db: int = 0, 
                 use_redis: bool = True):
        self.session_id = session_id
        self.interface = self.resolve_interface(interface)
        self.api_url = api_url
        self.api_token = api_token
        self.packet_count = 0
        self.running = True
        self.batch_size = 10
        self.packet_batch = []
        self.batch_lock = threading.Lock()
        
        # Redis configuration
        self.use_redis = use_redis and REDIS_AVAILABLE
        self.redis_client = None
        
        if self.use_redis:
            try:
                self.redis_client = redis.Redis(
                    host=redis_host,
                    port=redis_port,
                    db=redis_db,
                    decode_responses=False
                )
                # Test connection
                self.redis_client.ping()
                print(f"Connected to Redis at {redis_host}:{redis_port}")
            except Exception as e:
                print(f"Failed to connect to Redis: {e}")
                print("Falling back to webhook API")
                self.use_redis = False
                self.redis_client = None
        
        # Setup signal handlers for graceful shutdown
        signal.signal(signal.SIGINT, self.signal_handler)
        signal.signal(signal.SIGTERM, self.signal_handler)
        
        print(f"Initialized capture on interface: {self.interface}")
        print(f"Session ID: {session_id}")
        if self.use_redis:
            print(f"Using Redis queue: live_packets:{session_id}")
        else:
            print(f"API URL: {api_url}")
    
    def resolve_interface(self, interface_name: str) -> str:
        """
        Resolve friendly interface name to Npcap device name
        If already a device name, return as is
        """
        # If it's already a device name, use it
        if interface_name.startswith('\\Device\\NPF_'):
            return interface_name
        
        # Try to find matching device from available interfaces
        from scapy.all import get_if_list
        
        available = get_if_list()
        
        # First, try exact match
        if interface_name in available:
            return interface_name
        
        # If not found and interface is not a device name, use default
        print(f"Warning: Interface '{interface_name}' not found in available interfaces")
        print(f"Available interfaces: {available}")
        print(f"Using default interface: {conf.iface}")
        
        return conf.iface
    
    def signal_handler(self, signum, frame):
        """Handle shutdown signals"""
        print("\nReceived shutdown signal, stopping capture...")
        self.running = False
        self.send_remaining_packets()
        sys.exit(0)
    
    def analyze_packet(self, packet) -> Optional[Dict[str, Any]]:
        """Extract relevant information from a packet"""
        try:
            packet_data = {
                'captured_at': datetime.now().isoformat(),
                'source_ip': 'unknown',
                'destination_ip': 'unknown',
                'source_port': None,
                'destination_port': None,
                'protocol': 'unknown',
                'length': len(packet),
                'payload': None,
                'flags': None,
                'threat_score': 0.0,
                'threat_type': None,
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
                    
                    # Check for suspicious patterns
                    packet_data['threat_score'] = self.calculate_threat_score(packet, packet_data)
                
                # UDP Layer
                elif packet.haslayer(UDP):
                    udp_layer = packet[UDP]
                    packet_data['source_port'] = udp_layer.sport
                    packet_data['destination_port'] = udp_layer.dport
                    packet_data['protocol'] = 'UDP'
                    
                    packet_data['threat_score'] = self.calculate_threat_score(packet, packet_data)
                
                # ICMP Layer
                elif packet.haslayer(ICMP):
                    packet_data['protocol'] = 'ICMP'
                    packet_data['threat_score'] = self.calculate_threat_score(packet, packet_data)
            
            # ARP Layer
            elif packet.haslayer(ARP):
                arp_layer = packet[ARP]
                packet_data['source_ip'] = arp_layer.psrc
                packet_data['destination_ip'] = arp_layer.pdst
                packet_data['protocol'] = 'ARP'
            
            # Extract payload preview
            if packet.haslayer(Raw):
                raw_data = packet[Raw].load
                packet_data['payload'] = self.safe_decode(raw_data[:100])
            
            return packet_data
            
        except Exception as e:
            print(f"Error analyzing packet: {e}")
            return None
    
    def calculate_threat_score(self, packet, packet_data: Dict) -> float:
        """
        Calculate a simple threat score based on packet characteristics
        This is a basic implementation - in production, use ML model
        """
        score = 0.0
        
        # Suspicious ports
        suspicious_ports = [
            22, 23, 3389,  # Remote access
            135, 137, 138, 139, 445,  # SMB
            1433, 3306, 5432,  # Databases
        ]
        
        dest_port = packet_data.get('destination_port')
        if dest_port in suspicious_ports:
            score += 0.3
            packet_data['threat_type'] = 'Suspicious Port Access'
        
        # Port scanning detection (SYN flag without ACK)
        if packet_data.get('protocol') == 'TCP':
            flags = packet_data.get('flags', '')
            if 'S' in flags and 'A' not in flags:
                score += 0.2
                packet_data['threat_type'] = 'Possible Port Scan'
        
        # Unusual packet sizes
        length = packet_data.get('length', 0)
        if length > 1500 or length < 20:
            score += 0.1
        
        # ICMP flood detection
        if packet_data.get('protocol') == 'ICMP':
            score += 0.15
            packet_data['threat_type'] = 'ICMP Traffic'
        
        # Check for suspicious payload patterns
        payload = packet_data.get('payload', '')
        if payload:
            suspicious_patterns = ['../..', 'SELECT', 'UNION', 'DROP', '<script', 'eval(']
            for pattern in suspicious_patterns:
                if pattern.lower() in payload.lower():
                    score += 0.4
                    packet_data['threat_type'] = 'Suspicious Payload Pattern'
                    break
        
        return min(score, 1.0)  # Cap at 1.0
    
    def safe_decode(self, data: bytes) -> str:
        """Safely decode bytes to string"""
        try:
            return data.decode('utf-8', errors='ignore')
        except:
            return str(data)
    
    def send_packet_to_redis(self, packet_data: Dict) -> bool:
        """Send packet data to Redis queue"""
        try:
            queue_key = f"live_packets:{self.session_id}"
            data = json.dumps(packet_data)
            self.redis_client.rpush(queue_key, data)
            
            # Set TTL on queue to auto-expire after 1 hour of inactivity
            self.redis_client.expire(queue_key, 3600)
            
            return True
            
        except Exception as e:
            print(f"Failed to send packet to Redis: {e}")
            return False
    
    def send_packet_to_api(self, packet_data: Dict) -> bool:
        """Send packet data to Laravel API (fallback method)"""
        try:
            payload = {
                'session_id': self.session_id,
                'packet': packet_data,
                'api_token': self.api_token,
            }
            
            # Convert payload to JSON bytes
            data = json.dumps(payload).encode('utf-8')
            
            # Create request
            req = url_request.Request(
                self.api_url,
                data=data,
                headers={'Content-Type': 'application/json'},
                method='POST'
            )
            
            # Send request
            with url_request.urlopen(req, timeout=5) as response:
                if response.status != 200:
                    print(f"API Error: {response.status}")
                    return False
            
            return True
            
        except URLError as e:
            print(f"Failed to send packet to API: {e}")
            return False
        except Exception as e:
            print(f"Unexpected error sending packet: {e}")
            return False
    
    def send_batch(self):
        """Send accumulated packets in batch"""
        with self.batch_lock:
            if not self.packet_batch:
                return
            
            for packet_data in self.packet_batch:
                if self.use_redis:
                    self.send_packet_to_redis(packet_data)
                else:
                    self.send_packet_to_api(packet_data)
            
            self.packet_batch = []
    
    def send_remaining_packets(self):
        """Send any remaining packets in the batch"""
        print("Sending remaining packets...")
        self.send_batch()
    
    def packet_handler(self, packet):
        """Callback function for each captured packet"""
        if not self.running:
            return
        
        packet_data = self.analyze_packet(packet)
        
        if packet_data:
            self.packet_count += 1
            
            with self.batch_lock:
                self.packet_batch.append(packet_data)
            
            # Send batch if it reaches batch_size
            if len(self.packet_batch) >= self.batch_size:
                self.send_batch()
            
            # Print progress
            if self.packet_count % 100 == 0:
                print(f"Captured {self.packet_count} packets...")
    
    def start_capture(self):
        """Start capturing packets"""
        print(f"Starting packet capture on interface: {self.interface}")
        print("Press Ctrl+C to stop...")
        
        try:
            # Configure Scapy
            conf.verb = 0  # Disable verbose output
            
            # Start sniffing
            sniff(
                iface=self.interface,
                prn=self.packet_handler,
                store=False,
                stop_filter=lambda x: not self.running
            )
            
        except PermissionError:
            print("Error: Packet capture requires administrator/root privileges")
            print("Please run this script with elevated permissions")
            sys.exit(1)
        except Exception as e:
            print(f"Error during packet capture: {e}")
            sys.exit(1)
        finally:
            self.send_remaining_packets()
            print(f"\nCapture stopped. Total packets captured: {self.packet_count}")


def main():
    """Main entry point"""
    parser = argparse.ArgumentParser(
        description='Live Network Packet Capture for IDS/IPS System'
    )
    parser.add_argument(
        '--session-id',
        required=True,
        help='Monitoring session ID'
    )
    parser.add_argument(
        '--interface',
        required=True,
        help='Network interface to capture on'
    )
    parser.add_argument(
        '--api-url',
        required=False,
        help='API endpoint URL to send packets (fallback if Redis unavailable)'
    )
    parser.add_argument(
        '--api-token',
        required=False,
        help='API authentication token (fallback if Redis unavailable)'
    )
    parser.add_argument(
        '--redis-host',
        default='localhost',
        help='Redis host (default: localhost)'
    )
    parser.add_argument(
        '--redis-port',
        type=int,
        default=6379,
        help='Redis port (default: 6379)'
    )
    parser.add_argument(
        '--redis-db',
        type=int,
        default=0,
        help='Redis database number (default: 0)'
    )
    parser.add_argument(
        '--use-redis',
        action='store_true',
        default=True,
        help='Use Redis for packet transport (default: True)'
    )
    parser.add_argument(
        '--no-redis',
        action='store_true',
        help='Disable Redis and use webhook API instead'
    )
    
    args = parser.parse_args()
    
    # Determine transport method
    use_redis = args.use_redis and not args.no_redis
    
    # Validate arguments based on transport method
    if not use_redis and (not args.api_url or not args.api_token):
        parser.error('--api-url and --api-token are required when Redis is disabled')
    
    # Create and start capture
    capture = LivePacketCapture(
        session_id=args.session_id,
        interface=args.interface,
        api_url=args.api_url,
        api_token=args.api_token,
        redis_host=args.redis_host,
        redis_port=args.redis_port,
        redis_db=args.redis_db,
        use_redis=use_redis
    )
    
    capture.start_capture()


if __name__ == '__main__':
    main()
