#!/usr/bin/env python3
"""
Quick IDS Model Training Script
Trains essential models for intrusion detection
"""

import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.ensemble import RandomForestClassifier
from sklearn.preprocessing import StandardScaler, LabelEncoder
from sklearn.metrics import accuracy_score, classification_report
import joblib
import os
import json
from datetime import datetime
import warnings
warnings.filterwarnings('ignore')

def train_ids_model():
    """Quick training of IDS model"""
    print("Starting quick model training...")
    
    # Setup paths
    model_dir = os.path.join(os.path.dirname(__file__), "..", "ml_models")
    model_dir = os.path.abspath(model_dir)
    os.makedirs(model_dir, exist_ok=True)
    
    # Generate simple training data
    np.random.seed(42)
    n_samples = 10000
    
    # Features
    X = np.random.randn(n_samples, 20)  # 20 features
    
    # Labels (normal vs attack)
    y = np.random.choice(['normal', 'attack'], n_samples, p=[0.7, 0.3])
    
    # Add patterns to make it learnable
    X[y == 'attack', :5] += 2  # Attacks have different patterns
    X[y == 'normal', 5:10] += 1
    
    # Encode labels
    le = LabelEncoder()
    y_encoded = le.fit_transform(y)
    
    # Scale features
    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X)
    
    # Split data
    X_train, X_test, y_train, y_test = train_test_split(
        X_scaled, y_encoded, test_size=0.2, random_state=42
    )
    
    # Train Random Forest (fastest and most reliable)
    print("Training Random Forest model...")
    model = RandomForestClassifier(
        n_estimators=50,  # Reduced for speed
        max_depth=10,
        random_state=42,
        n_jobs=-1
    )
    model.fit(X_train, y_train)
    
    # Evaluate
    y_pred = model.predict(X_test)
    accuracy = accuracy_score(y_test, y_pred)
    print(f"Model accuracy: {accuracy:.4f}")
    
    # Save model and preprocessors
    joblib.dump(model, os.path.join(model_dir, "random_forest_model.pkl"))
    joblib.dump(scaler, os.path.join(model_dir, "scaler.pkl"))
    joblib.dump(le, os.path.join(model_dir, "label_encoder.pkl"))
    
    # Save metadata
    metadata = {
        'training_date': datetime.now().isoformat(),
        'model_type': 'RandomForestClassifier',
        'accuracy': float(accuracy),
        'n_features': 20,
        'classes': list(le.classes_),
        'n_samples': n_samples,
        'feature_names': [f"feature_{i}" for i in range(20)]
    }
    
    with open(os.path.join(model_dir, "model_metadata.json"), 'w') as f:
        json.dump(metadata, f, indent=2)
    
    print(f"Model training completed!")
    print(f"Models saved in: {os.path.abspath(model_dir)}")
    
    # Save a default best model indicator
    with open(os.path.join(model_dir, "best_model.txt"), 'w') as f:
        f.write("random_forest_model.pkl")

if __name__ == "__main__":
    train_ids_model()
