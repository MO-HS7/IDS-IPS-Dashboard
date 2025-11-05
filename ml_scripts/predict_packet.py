#!/usr/bin/env python3
"""
Enhanced IDS Prediction Script
Analyzes network packets and returns threat predictions
"""

import sys
import json
import joblib
import numpy as np
import os
from datetime import datetime

class PacketPredictor:
    def __init__(self, model_dir="../ml_models"):
        """Initialize predictor with trained models"""
        self.model_dir = os.path.abspath(model_dir)
        self.model = None
        self.scaler = None
        self.label_encoder = None
        self.load_models()
    
    def load_models(self):
        """Load trained ML models"""
        try:
            model_path = os.path.join(self.model_dir, "random_forest_model.pkl")
            scaler_path = os.path.join(self.model_dir, "scaler.pkl")
            encoder_path = os.path.join(self.model_dir, "label_encoder.pkl")
            
            self.model = joblib.load(model_path)
            self.scaler = joblib.load(scaler_path)
            self.label_encoder = joblib.load(encoder_path)
            
            return True
        except Exception as e:
            print(json.dumps({"error": f"Failed to load models: {str(e)}"}))
            return False
    
    def extract_features(self, packet_data):
        """
        Extract features from packet data
        Expected input: dict with packet information
        Returns: numpy array of 20 features
        """
        try:
            # Extract basic features from packet
            features = [
                packet_data.get('length', 0),
                packet_data.get('protocol', 0),
                packet_data.get('src_port', 0),
                packet_data.get('dst_port', 0),
                packet_data.get('flags_syn', 0),
                packet_data.get('flags_ack', 0),
                packet_data.get('flags_fin', 0),
                packet_data.get('flags_rst', 0),
                packet_data.get('flags_psh', 0),
                packet_data.get('flags_urg', 0),
                packet_data.get('ttl', 64),
                packet_data.get('window_size', 0),
                packet_data.get('payload_size', 0),
                packet_data.get('tcp_seq', 0),
                packet_data.get('tcp_ack', 0),
                packet_data.get('ip_id', 0),
                packet_data.get('fragment_offset', 0),
                packet_data.get('checksum', 0),
                packet_data.get('options_length', 0),
                packet_data.get('reserved', 0),
            ]
            
            return np.array(features).reshape(1, -1)
        except Exception as e:
            # Return default features if extraction fails
            return np.zeros((1, 20))
    
    def predict(self, packet_data):
        """
        Predict if packet is malicious
        Returns: dict with prediction results
        """
        try:
            # Extract features
            features = self.extract_features(packet_data)
            
            # Scale features
            features_scaled = self.scaler.transform(features)
            
            # Predict
            prediction = self.model.predict(features_scaled)[0]
            probability = self.model.predict_proba(features_scaled)[0]
            
            # Get label
            label = self.label_encoder.inverse_transform([prediction])[0]
            
            # Calculate confidence and severity
            confidence = float(np.max(probability) * 100)
            is_attack = label != 'normal'
            
            # Calculate severity based on confidence
            if is_attack:
                if confidence >= 95:
                    severity = 'critical'
                    severity_score = 90
                elif confidence >= 85:
                    severity = 'high'
                    severity_score = 75
                elif confidence >= 70:
                    severity = 'medium'
                    severity_score = 50
                else:
                    severity = 'low'
                    severity_score = 25
            else:
                severity = 'info'
                severity_score = 0
            
            result = {
                "prediction": label,
                "is_attack": is_attack,
                "confidence": round(confidence, 2),
                "severity": severity,
                "severity_score": severity_score,
                "probabilities": {
                    str(self.label_encoder.classes_[i]): round(float(probability[i]) * 100, 2)
                    for i in range(len(probability))
                },
                "timestamp": datetime.now().isoformat(),
                "model_version": "1.0.0"
            }
            
            return result
            
        except Exception as e:
            return {
                "error": str(e),
                "prediction": "unknown",
                "is_attack": False,
                "confidence": 0,
                "severity": "info"
            }
    
    def predict_batch(self, packets_data):
        """
        Predict multiple packets at once
        Returns: list of prediction results
        """
        results = []
        for packet_data in packets_data:
            result = self.predict(packet_data)
            results.append(result)
        return results

def main():
    """Main function for CLI usage"""
    if len(sys.argv) < 2:
        print(json.dumps({
            "error": "Usage: python predict_packet.py <packet_json>"
        }))
        sys.exit(1)
    
    try:
        # Parse input
        packet_json = sys.argv[1]
        packet_data = json.loads(packet_json)
        
        # Initialize predictor
        predictor = PacketPredictor()
        
        # Make prediction
        if isinstance(packet_data, list):
            result = predictor.predict_batch(packet_data)
        else:
            result = predictor.predict(packet_data)
        
        # Output result as JSON
        print(json.dumps(result, indent=2))
        
    except json.JSONDecodeError:
        print(json.dumps({"error": "Invalid JSON input"}))
        sys.exit(1)
    except Exception as e:
        print(json.dumps({"error": str(e)}))
        sys.exit(1)

if __name__ == "__main__":
    main()
