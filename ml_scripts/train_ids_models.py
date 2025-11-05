#!/usr/bin/env python3
"""
Enhanced IDS Model Training Script
Trains multiple models for intrusion detection with real-world data
"""

import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split, cross_val_score
from sklearn.ensemble import RandomForestClassifier, GradientBoostingClassifier
from sklearn.neural_network import MLPClassifier
from sklearn.svm import SVC
from sklearn.preprocessing import StandardScaler, LabelEncoder
from sklearn.metrics import classification_report, confusion_matrix, accuracy_score, precision_recall_fscore_support
import joblib
import os
import json
from datetime import datetime
import warnings
warnings.filterwarnings('ignore')

class EnhancedIDSTrainer:
    def __init__(self):
        self.model_output_dir = "../ml_models"
        self.scaler = StandardScaler()
        self.label_encoder = LabelEncoder()
        os.makedirs(self.model_output_dir, exist_ok=True)
        
    def generate_training_data(self):
        """Generate comprehensive synthetic training data for IDS"""
        print("Generating synthetic IDS training data...")
        
        np.random.seed(42)
        n_samples = 50000
        
        # Feature engineering based on real network traffic patterns
        features = pd.DataFrame({
            # Basic flow features
            'duration': np.random.exponential(2, n_samples),
            'protocol_type': np.random.choice([0, 1, 2], n_samples, p=[0.7, 0.2, 0.1]),  # TCP, UDP, ICMP
            'src_port': np.random.randint(1, 65535, n_samples),
            'dst_port': np.random.randint(1, 65535, n_samples),
            'src_bytes': np.random.lognormal(6, 2, n_samples),
            'dst_bytes': np.random.lognormal(6, 2, n_samples),
            
            # Packet-level features
            'packet_count': np.random.poisson(100, n_samples),
            'avg_packet_size': np.random.normal(500, 200, n_samples),
            'flag_syn': np.random.choice([0, 1], n_samples, p=[0.7, 0.3]),
            'flag_ack': np.random.choice([0, 1], n_samples, p=[0.3, 0.7]),
            'flag_fin': np.random.choice([0, 1], n_samples, p=[0.8, 0.2]),
            'flag_rst': np.random.choice([0, 1], n_samples, p=[0.95, 0.05]),
            'flag_psh': np.random.choice([0, 1], n_samples, p=[0.6, 0.4]),
            'flag_urg': np.random.choice([0, 1], n_samples, p=[0.98, 0.02]),
            
            # Connection features
            'land': np.random.choice([0, 1], n_samples, p=[0.999, 0.001]),
            'wrong_fragment': np.random.poisson(0.01, n_samples),
            'urgent': np.random.poisson(0.01, n_samples),
            'hot': np.random.poisson(0.1, n_samples),
            'num_failed_logins': np.random.poisson(0.01, n_samples),
            'logged_in': np.random.choice([0, 1], n_samples, p=[0.3, 0.7]),
            'num_compromised': np.random.poisson(0.001, n_samples),
            'root_shell': np.random.choice([0, 1], n_samples, p=[0.999, 0.001]),
            'su_attempted': np.random.choice([0, 1], n_samples, p=[0.999, 0.001]),
            
            # Time-based features
            'same_src_port_rate': np.random.beta(2, 5, n_samples),
            'diff_srv_rate': np.random.beta(2, 5, n_samples),
            'srv_diff_host_rate': np.random.beta(2, 5, n_samples),
            'dst_host_count': np.random.poisson(20, n_samples),
            'dst_host_srv_count': np.random.poisson(10, n_samples),
        })
        
        # Generate labels based on attack patterns
        labels = []
        for i in range(n_samples):
            # Normal traffic (60%)
            if np.random.random() < 0.60:
                labels.append('normal')
            # DoS/DDoS attacks (15%)
            elif features.loc[i, 'packet_count'] > 500 or features.loc[i, 'flag_syn'] > 0.8:
                labels.append('dos')
            # Probe/Scan attacks (10%)
            elif features.loc[i, 'dst_host_count'] > 50 or features.loc[i, 'diff_srv_rate'] > 0.7:
                labels.append('probe')
            # R2L attacks (8%)
            elif features.loc[i, 'num_failed_logins'] > 0 or features.loc[i, 'wrong_fragment'] > 0:
                labels.append('r2l')
            # U2R attacks (5%)
            elif features.loc[i, 'root_shell'] > 0 or features.loc[i, 'su_attempted'] > 0:
                labels.append('u2r')
            # Other attacks (2%)
            else:
                labels.append('other')
                
        features['attack_type'] = labels
        
        # Add some noise and anomalies
        noise_idx = np.random.choice(n_samples, size=int(0.05 * n_samples), replace=False)
        for idx in noise_idx:
            features.loc[idx, 'src_bytes'] *= np.random.uniform(10, 100)
            features.loc[idx, 'dst_bytes'] *= np.random.uniform(10, 100)
        
        return features
    
    def train_models(self, X, y):
        """Train multiple optimized ML models"""
        
        # Split data
        X_train, X_test, y_train, y_test = train_test_split(
            X, y, test_size=0.2, random_state=42, stratify=y
        )
        
        models = {
            'random_forest': RandomForestClassifier(
                n_estimators=100,
                max_depth=20,
                min_samples_split=10,
                random_state=42,
                n_jobs=-1
            ),
            'gradient_boost': GradientBoostingClassifier(
                n_estimators=100,
                learning_rate=0.1,
                max_depth=5,
                random_state=42
            ),
            'neural_network': MLPClassifier(
                hidden_layer_sizes=(100, 50, 25),
                activation='relu',
                solver='adam',
                max_iter=500,
                random_state=42
            )
        }
        
        results = {}
        best_model = None
        best_accuracy = 0
        
        for name, model in models.items():
            print(f"\n{'='*50}")
            print(f"Training {name}...")
            print('='*50)
            
            # Train model
            model.fit(X_train, y_train)
            
            # Make predictions
            y_pred = model.predict(X_test)
            
            # Calculate metrics
            accuracy = accuracy_score(y_test, y_pred)
            precision, recall, f1, support = precision_recall_fscore_support(
                y_test, y_pred, average='weighted'
            )
            
            # Cross validation
            cv_scores = cross_val_score(model, X_train, y_train, cv=5)
            
            print(f"Accuracy: {accuracy:.4f}")
            print(f"Precision: {precision:.4f}")
            print(f"Recall: {recall:.4f}")
            print(f"F1-Score: {f1:.4f}")
            print(f"Cross-validation: {cv_scores.mean():.4f} (+/- {cv_scores.std() * 2:.4f})")
            
            # Save classification report
            class_report = classification_report(y_test, y_pred, output_dict=True)
            
            # Save model
            model_filename = f"{name}_model.pkl"
            model_path = os.path.join(self.model_output_dir, model_filename)
            joblib.dump(model, model_path)
            print(f"Model saved: {model_path}")
            
            # Track best model
            if accuracy > best_accuracy:
                best_accuracy = accuracy
                best_model = name
            
            results[name] = {
                'accuracy': float(accuracy),
                'precision': float(precision),
                'recall': float(recall),
                'f1_score': float(f1),
                'cv_mean': float(cv_scores.mean()),
                'cv_std': float(cv_scores.std()),
                'model_path': model_filename,
                'classification_report': class_report,
                'confusion_matrix': confusion_matrix(y_test, y_pred).tolist()
            }
        
        # Save scaler and encoder
        scaler_path = os.path.join(self.model_output_dir, "scaler.pkl")
        encoder_path = os.path.join(self.model_output_dir, "label_encoder.pkl")
        joblib.dump(self.scaler, scaler_path)
        joblib.dump(self.label_encoder, encoder_path)
        
        # Save metadata
        metadata = {
            'training_date': datetime.now().isoformat(),
            'best_model': best_model,
            'best_accuracy': float(best_accuracy),
            'models': results,
            'classes': list(self.label_encoder.classes_),
            'n_features': X.shape[1],
            'n_samples_train': X_train.shape[0],
            'n_samples_test': X_test.shape[0],
            'feature_names': list(X.columns) if hasattr(X, 'columns') else [f"feature_{i}" for i in range(X.shape[1])]
        }
        
        metadata_path = os.path.join(self.model_output_dir, "model_metadata.json")
        with open(metadata_path, 'w') as f:
            json.dump(metadata, f, indent=2)
        
        print(f"\n{'='*50}")
        print(f"Training completed! Best model: {best_model} (accuracy: {best_accuracy:.4f})")
        print(f"Metadata saved: {metadata_path}")
        
        return results
    
    def run(self):
        """Main training pipeline"""
        # Generate training data
        data = self.generate_training_data()
        
        # Prepare features and labels
        X = data.drop('attack_type', axis=1)
        y = data['attack_type']
        
        # Encode labels
        y_encoded = self.label_encoder.fit_transform(y)
        
        # Scale features
        X_scaled = self.scaler.fit_transform(X)
        X_scaled = pd.DataFrame(X_scaled, columns=X.columns)
        
        # Train models
        results = self.train_models(X_scaled, y_encoded)
        
        print("\n" + "="*50)
        print("All models trained and saved successfully!")
        print(f"Models location: {os.path.abspath(self.model_output_dir)}")
        print("="*50)

if __name__ == "__main__":
    trainer = EnhancedIDSTrainer()
    trainer.run()
