#!/usr/bin/env python3
"""
Enhanced IDS-IPS Pipeline with Research Paper Insights
Implements hybrid ML + Snort detection with TShark integration
Based on "Developing a real-time IDPS using Snort with Machine Learning" research
"""

import os
import sys
import json
import time
import threading
import subprocess
import queue
from datetime import datetime
from typing import Dict, List, Any, Optional, Tuple
from collections import defaultdict
import joblib
import pandas as pd
import numpy as np
from scapy.all import IP, TCP, UDP, ICMP, Raw
import hashlib

# Import ML libraries
try:
    from sklearn.ensemble import RandomForestClassifier
    from sklearn.preprocessing import StandardScaler
    from sklearn.metrics import classification_report
    SKLEARN_AVAILABLE = True
except ImportError:
    SKLEARN_AVAILABLE = False

# Import scapy with error handling
try:
    from scapy.all import sniff, IP, TCP, UDP, ICMP, Raw, conf
    SCAPY_AVAILABLE = True
except ImportError:
    SCAPY_AVAILABLE = False

class FlowExtractor:
    """Extract flow-based features as described in research paper"""
    
    def __init__(self):
        self.flows = {}
        self.flow_timeout = 30  # seconds
        self.packet_limit = 1000  # packets per flow
        
    def process_packet(self, packet) -> Optional[Dict[str, Any]]:
        """Process packet and extract flow features"""
        if not packet.haslayer(IP):
            return None
            
        ip_layer = packet[IP]
        src_ip = ip_layer.src
        dst_ip = ip_layer.dst
        proto = ip_layer.proto
        
        # Create flow key
        if packet.haslayer(TCP):
            tcp_layer = packet[TCP]
            flow_key = f"{src_ip}:{tcp_layer.sport}-{dst_ip}:{tcp_layer.dport}-{proto}"
        elif packet.haslayer(UDP):
            udp_layer = packet[UDP]
            flow_key = f"{src_ip}:{udp_layer.sport}-{dst_ip}:{udp_layer.dport}-{proto}"
        else:
            flow_key = f"{src_ip}-00-{dst_ip}-00-{proto}"
        
        current_time = time.time()
        
        # Initialize or update flow
        if flow_key not in self.flows:
            self.flows[flow_key] = {
                'start_time': current_time,
                'src_packets': [],
                'dst_packets': [],
                'src_bytes': 0,
                'dst_bytes': 0,
                'protocol': proto,
                'src_ip': src_ip,
                'dst_ip': dst_ip,
                'total_packets': 0
            }
        
        flow = self.flows[flow_key]
        
        # Update flow statistics
        packet_size = len(packet)
        if src_ip == flow['src_ip']:
            flow['src_packets'].append({
                'timestamp': current_time,
                'size': packet_size,
                'flags': packet[TCP].flags if packet.haslayer(TCP) else None
            })
            flow['src_bytes'] += packet_size
        else:
            flow['dst_packets'].append({
                'timestamp': current_time,
                'size': packet_size,
                'flags': packet[TCP].flags if packet.haslayer(TCP) else None
            })
            flow['dst_bytes'] += packet_size
            
        flow['total_packets'] += 1
        
        # Check if flow should be analyzed
        if (current_time - flow['start_time'] >= self.flow_timeout or 
            flow['total_packets'] >= self.packet_limit):
            return self.extract_features(flow_key)
        
        return None
    
    def extract_features(self, flow_key: str) -> Dict[str, Any]:
        """Extract flow-based features as specified in research paper"""
        if flow_key not in self.flows:
            return {}
            
        flow = self.flows[flow_key]
        
        features = {
            'flow_key': flow_key,
            'src_ip': flow['src_ip'],
            'dst_ip': flow['dst_ip'],
            'protocol': flow['protocol'],
            'flow_duration': time.time() - flow['start_time'],
            'total_packets': flow['total_packets'],
            'src_packets': len(flow['src_packets']),
            'dst_packets': len(flow['dst_packets']),
            'total_bytes': flow['src_bytes'] + flow['dst_bytes'],
            'src_bytes': flow['src_bytes'],
            'dst_bytes': flow['dst_bytes']
        }
        
        # Calculate packet size statistics
        all_packets = flow['src_packets'] + flow['dst_packets']
        if all_packets:
            packet_sizes = [p['size'] for p in all_packets]
            timestamps = [p['timestamp'] for p in all_packets]
            
            features.update({
                'avg_packet_size': np.mean(packet_sizes),
                'min_packet_size': min(packet_sizes),
                'max_packet_size': max(packet_sizes),
                'packet_size_std': np.std(packet_sizes) if len(packet_sizes) > 1 else 0
            })
            
            # Calculate time gaps between packets
            if len(timestamps) > 1:
                time_gaps = np.diff(timestamps)
                features.update({
                    'avg_time_gap': np.mean(time_gaps),
                    'min_time_gap': min(time_gaps),
                    'max_time_gap': max(time_gaps),
                    'time_gap_std': np.std(time_gaps) if len(time_gaps) > 1 else 0
                })
                # Bytes per second
                if features['flow_duration'] > 0:
                    features['bytes_per_second'] = features['total_bytes'] / features['flow_duration']
            else:
                features.update({
                    'avg_time_gap': 0, 'min_time_gap': 0, 'max_time_gap': 0, 
                    'time_gap_std': 0, 'bytes_per_second': 0
                })
        
        # Protocol-specific features
        features.update({
            'tcp_urgent_flags': 0,
            'tcp_psh_flags': 0,
            'protocol_type': 'TCP' if flow['protocol'] == 6 else 
                           'UDP' if flow['protocol'] == 17 else 'OTHER'
        })
        
        # Remove flow after extraction
        del self.flows[flow_key]
        
        return features

class MLThreatDetector:
    """Random Forest based threat detection as per research paper"""
    
    def __init__(self, model_path: str = None):
        self.model_path = model_path
        self.model = None
        self.scaler = None
        self.label_encoder = None
        self.load_model()
        
    def load_model(self):
        """Load trained ML model"""
        if not SKLEARN_AVAILABLE:
            print("Warning: scikit-learn not available, using basic heuristics")
            return
            
        if self.model_path and os.path.exists(self.model_path):
            try:
                self.model = joblib.load(self.model_path)
                scaler_path = self.model_path.replace('_model.pkl', '_scaler.pkl')
                encoder_path = self.model_path.replace('_model.pkl', '_encoder.pkl')
                
                if os.path.exists(scaler_path):
                    self.scaler = joblib.load(scaler_path)
                if os.path.exists(encoder_path):
                    self.label_encoder = joblib.load(encoder_path)
                    
                print(f"Loaded ML model from {self.model_path}")
            except Exception as e:
                print(f"Error loading model: {e}")
                self.model = None
    
    def predict_threat(self, flow_features: Dict[str, Any]) -> Tuple[float, str, str]:
        """Predict threat using trained model"""
        if not self.model:
            return self.heuristic_detection(flow_features)
            
        try:
            # Convert features to ML input format
            feature_vector = self._features_to_vector(flow_features)
            
            if self.scaler:
                feature_vector = self.scaler.transform([feature_vector])
            
            # Binary classification (normal/attack)
            binary_pred = self.model.predict(feature_vector)[0]
            if hasattr(self.model, 'predict_proba'):
                confidence = max(self.model.predict_proba(feature_vector)[0])
            else:
                confidence = 1.0 if binary_pred == 1 else 0.7
            
            # Multiclass classification (attack type)
            attack_type = "normal" if binary_pred == 0 else self._get_attack_type(flow_features)
            
            return confidence, "attack" if binary_pred == 1 else "normal", attack_type
            
        except Exception as e:
            print(f"ML prediction error: {e}")
            return self.heuristic_detection(flow_features)
    
    def heuristic_detection(self, flow_features: Dict[str, Any]) -> Tuple[float, str, str]:
        """Fallback heuristic detection"""
        threat_score = 0.0
        attack_type = "normal"
        
        # Large packet size (potential DoS)
        if flow_features.get('avg_packet_size', 0) > 1400:
            threat_score += 0.4
            attack_type = "DoS"
        
        # High packet rate
        if flow_features.get('total_packets', 0) > 50:
            threat_score += 0.3
        
        # Unusual protocol
        if flow_features.get('protocol_type') not in ['TCP', 'UDP']:
            threat_score += 0.3
            attack_type = "PortScan"
        
        # High bytes per second (potential data exfiltration)
        if flow_features.get('bytes_per_second', 0) > 10000:
            threat_score += 0.2
        
        return min(threat_score, 1.0), "attack" if threat_score > 0.5 else "normal", attack_type
    
    def _features_to_vector(self, flow_features: Dict[str, Any]) -> List[float]:
        """Convert flow features to ML feature vector"""
        # Match the feature names used in training
        features = [
            flow_features.get('flow_duration', 0),
            6 if flow_features.get('protocol') == 6 else 17 if flow_features.get('protocol') == 17 else 0,
            flow_features.get('total_packets', 0),
            flow_features.get('total_bytes', 0),
            flow_features.get('avg_packet_size', 0),
            flow_features.get('bytes_per_second', 0),
            flow_features.get('src_packets', 0),
            flow_features.get('dst_packets', 0),
            flow_features.get('packet_size_std', 0),
            flow_features.get('time_gap_std', 0)
        ]
        return features
    
    def _get_attack_type(self, flow_features: Dict[str, Any]) -> str:
        """Determine attack type based on flow characteristics"""
        if flow_features.get('avg_packet_size', 0) > 1400:
            return "DoS"
        elif flow_features.get('protocol_type') not in ['TCP', 'UDP']:
            return "PortScan"
        elif flow_features.get('bytes_per_second', 0) > 50000:
            return "DDoS"
        else:
            return "AnomalousTraffic"

class SnortValidator:
    """Snort rule validation as per research paper"""
    
    def __init__(self, snort_rules_path: str = None):
        self.snort_rules_path = snort_rules_path
        self.load_snort_rules()
    
    def load_snort_rules(self):
        """Load Snort rules for validation"""
        self.snort_rules = []
        if self.snort_rules_path and os.path.exists(self.snort_rules_path):
            try:
                with open(self.snort_rules_path, 'r') as f:
                    self.snort_rules = [line.strip() for line in f if line.strip() and not line.startswith('#')]
                print(f"Loaded {len(self.snort_rules)} Snort rules")
            except Exception as e:
                print(f"Error loading Snort rules: {e}")
    
    def validate_flow(self, flow_features: Dict[str, Any]) -> Tuple[bool, str, float]:
        """Validate flow against Snort rules"""
        # Basic rule matching based on flow characteristics
        src_ip = flow_features.get('src_ip', '')
        dst_ip = flow_features.get('dst_ip', '')
        protocol = flow_features.get('protocol_type', '')
        total_packets = flow_features.get('total_packets', 0)
        
        # Example Snort-like rules
        rules_matched = []
        
        # Rule: Detect port scanning (many packets to different ports)
        if total_packets > 30:
            rules_matched.append("Port scan detected")
        
        # Rule: Detect DoS attacks (large packets)
        avg_size = flow_features.get('avg_packet_size', 0)
        if avg_size > 1400:
            rules_matched.append("Potential DoS attack (large packets)")
        
        # Rule: Detect unusual protocols
        if protocol == 'OTHER':
            rules_matched.append("Unusual protocol detected")
        
        # Check against loaded rules
        for rule in self.snort_rules:
            if self._rule_matches(rule, flow_features):
                rules_matched.append(rule)
        
        return len(rules_matched) > 0, "; ".join(rules_matched), 0.9 if rules_matched else 0.1
    
    def _rule_matches(self, rule: str, flow_features: Dict[str, Any]) -> bool:
        """Simple rule matching logic"""
        # This is a simplified implementation
        # In practice, this would parse Snort rule syntax
        rule_lower = rule.lower()
        if "port" in rule_lower and flow_features.get('total_packets', 0) > 20:
            return True
        if "dos" in rule_lower and flow_features.get('avg_packet_size', 0) > 1400:
            return True
        return False

class DualVerificationSystem:
    """Implements hybrid detection as per research paper"""
    
    def __init__(self, model_path: str = None, snort_rules_path: str = None):
        self.ml_detector = MLThreatDetector(model_path)
        self.snort_validator = SnortValidator(snort_rules_path)
        self.flow_extractor = FlowExtractor()
        
    def analyze_flow(self, flow_features: Dict[str, Any]) -> Dict[str, Any]:
        """Perform dual verification: ML prediction + Snort validation"""
        # ML Analysis
        ml_confidence, ml_classification, ml_attack_type = self.ml_detector.predict_threat(flow_features)
        
        # Snort Validation
        snort_matched, snort_message, snort_confidence = self.snort_validator.validate_flow(flow_features)
        
        # Combine results (as per research paper: a flow is an "attack" if either model alerts)
        is_attack = ml_classification == "attack" or snort_matched
        
        # Determine final classification and confidence
        if is_attack:
            final_classification = "attack"
            final_attack_type = ml_attack_type if ml_classification == "attack" else "SnortRuleViolation"
            final_confidence = max(ml_confidence, snort_confidence)
            confidence_level = self._get_confidence_level(final_confidence)
        else:
            final_classification = "normal"
            final_attack_type = "None"
            final_confidence = 1.0 - max(ml_confidence, snort_confidence)
            confidence_level = "low"
        
        return {
            'flow_key': flow_features.get('flow_key'),
            'src_ip': flow_features.get('src_ip'),
            'dst_ip': flow_features.get('dst_ip'),
            'protocol': flow_features.get('protocol_type'),
            'classification': final_classification,
            'attack_type': final_attack_type,
            'confidence': final_confidence,
            'confidence_level': confidence_level,
            'ml_result': {
                'confidence': ml_confidence,
                'classification': ml_classification,
                'attack_type': ml_attack_type
            },
            'snort_result': {
                'matched': snort_matched,
                'message': snort_message,
                'confidence': snort_confidence
            },
            'timestamp': datetime.now().isoformat()
        }
    
    def _get_confidence_level(self, confidence: float) -> str:
        """Convert numerical confidence to qualitative level"""
        if confidence >= 0.8:
            return "high"
        elif confidence >= 0.5:
            return "medium"
        else:
            return "low"

class RealTimeIDPS:
    """Main IDPS system with research paper enhancements"""
    
    def __init__(self, model_path: str = None, snort_rules_path: str = None):
        self.dual_verification = DualVerificationSystem(model_path, snort_rules_path)
        self.packet_queue = queue.Queue(maxsize=1000)
        self.result_queue = queue.Queue()
        self.running = False
        
    def start_realtime_monitoring(self, interface: str = 'eth0', tshark_path: str = None):
        """Start real-time monitoring using TShark or Scapy"""
        self.running = True
        
        if tshark_path:
            self._start_tshark_monitoring(tshark_path, interface)
        else:
            self._start_scapy_monitoring(interface)
    
    def _start_tshark_monitoring(self, tshark_path: str, interface: str):
        """Start monitoring using TShark as per research paper"""
        try:
            cmd = [tshark_path, '-i', interface, '-T', 'fields', 
                   '-e', 'ip.src', '-e', 'ip.dst', '-e', 'ip.proto',
                   '-e', 'tcp.flags', '-e', 'udp.srcport', '-e', 'udp.dstport',
                   '-e', 'frame.len']
            
            process = subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
            
            while self.running:
                line = process.stdout.readline()
                if line:
                    try:
                        self._process_tshark_line(line)
                    except Exception as e:
                        print(f"Error processing TShark line: {e}")
                        
        except Exception as e:
            print(f"TShark monitoring error: {e}")
            self._start_scapy_monitoring(interface)  # Fallback
    
    def _process_tshark_line(self, line: str):
        """Process TShark output line"""
        fields = line.strip().split('\t')
        if len(fields) >= 6:
            packet_data = {
                'timestamp': datetime.now().isoformat(),
                'src_ip': fields[0],
                'dst_ip': fields[1],
                'protocol': int(fields[2]) if fields[2].isdigit() else 0,
                'flags': fields[3] if len(fields) > 3 else '',
                'src_port': fields[4] if len(fields) > 4 and fields[4].isdigit() else 0,
                'dst_port': fields[5] if len(fields) > 5 and fields[5].isdigit() else 0,
                'length': int(fields[6]) if len(fields) > 6 and fields[6].isdigit() else 0
            }
            
            # Add to flow processing
            self._process_packet_data(packet_data)
    
    def _start_scapy_monitoring(self, interface: str):
        """Start monitoring using Scapy as fallback"""
        if not SCAPY_AVAILABLE:
            print("Cannot start monitoring: Scapy not available")
            return
            
        def packet_callback(packet):
            if not self.running:
                return
                
            packet_data = self._scapy_to_dict(packet)
            if packet_data:
                self._process_packet_data(packet_data)
        
        try:
            sniff(iface=interface, prn=packet_callback, stop_filter=lambda x: not self.running)
        except Exception as e:
            print(f"Scapy monitoring error: {e}")
    
    def _scapy_to_dict(self, packet) -> Dict[str, Any]:
        """Convert Scapy packet to dictionary"""
        if not packet.haslayer(IP):
            return None
            
        ip_layer = packet[IP]
        
        packet_data = {
            'timestamp': datetime.now().isoformat(),
            'src_ip': ip_layer.src,
            'dst_ip': ip_layer.dst,
            'protocol': ip_layer.proto,
            'length': len(packet)
        }
        
        if packet.haslayer(TCP):
            tcp_layer = packet[TCP]
            packet_data.update({
                'src_port': tcp_layer.sport,
                'dst_port': tcp_layer.dport,
                'flags': str(tcp_layer.flags)
            })
        elif packet.haslayer(UDP):
            udp_layer = packet[UDP]
            packet_data.update({
                'src_port': udp_layer.sport,
                'dst_port': udp_layer.dport,
                'flags': ''
            })
        
        return packet_data
    
    def _process_packet_data(self, packet_data: Dict[str, Any]):
        """Process packet data and perform dual verification"""
        try:
            # Extract flow features
            flow_features = self.flow_extractor.extract_flow_features(packet_data)
            
            if flow_features:
                # Perform dual verification
                result = self.dual_verification.analyze_flow(flow_features)
                
                # Add to results queue
                self.result_queue.put(result)
                
                # Log result
                self._log_result(result)
                
        except Exception as e:
            print(f"Error processing packet: {e}")
    
    def _log_result(self, result: Dict[str, Any]):
        """Log detection results as per research paper"""
        if result['classification'] == 'attack':
            print(f"[ATTACK DETECTED] {result['attack_type']} from {result['src_ip']} to {result['dst_ip']} "
                  f"(Confidence: {result['confidence_level']}, {result['confidence']:.2f})")
        else:
            print(f"[NORMAL TRAFFIC] {result['src_ip']} -> {result['dst_ip']}")
    
    def stop(self):
        """Stop monitoring"""
        self.running = False
        print("IDPS monitoring stopped")

def main():
    """Main function"""
    import argparse
    
    parser = argparse.ArgumentParser(description='Enhanced Real-time IDPS System')
    parser.add_argument('--interface', default='eth0', help='Network interface to monitor')
    parser.add_argument('--tshark-path', help='Path to TShark executable')
    parser.add_argument('--model-path', help='Path to trained ML model')
    parser.add_argument('--snort-rules', help='Path to Snort rules file')
    
    args = parser.parse_args()
    
    print("Starting Enhanced Real-time IDPS System")
    print("Based on: Developing a real-time IDPS using Snort with Machine Learning")
    
    # Initialize IDPS
    idps = RealTimeIDPS(
        model_path=args.model_path,
        snort_rules_path=args.snort_rules
    )
    
    try:
        # Start monitoring
        idps.start_realtime_monitoring(
            interface=args.interface,
            tshark_path=args.tshark_path
        )
    except KeyboardInterrupt:
        print("\nShutting down...")
        idps.stop()

if __name__ == "__main__":
    main()