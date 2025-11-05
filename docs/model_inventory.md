# ML Model Inventory

## Current Models

### Random Forest IDS Model
- **Path**: `ml_models/random_forest_model.pkl`
- **Type**: RandomForestClassifier
- **Size**: 1.2 MB
- **Training Date**: 2025-11-05
- **Accuracy**: 99.15%
- **Classes**: `['attack', 'normal']`
- **Features**: 20 numerical features
- **Status**: ACTIVE (Default model)

### Preprocessors
- **Scaler**: `ml_models/scaler.pkl` - StandardScaler for feature normalization
- **Label Encoder**: `ml_models/label_encoder.pkl` - Maps attack types to numeric labels

## Model Metadata
```json
{
  "training_date": "2025-11-05T02:50:00",
  "model_type": "RandomForestClassifier",
  "accuracy": 0.9915,
  "n_features": 20,
  "classes": ["attack", "normal"],
  "n_samples": 10000,
  "feature_names": ["feature_0", "feature_1", ..., "feature_19"]
}
```

## Feature Extraction Pipeline

### Expected Input Features
The model expects 20 numerical features extracted from network packets:
1. Packet size statistics
2. Protocol indicators
3. Port numbers
4. Flag combinations
5. Time-based features
6. Connection statistics

### Feature Extraction Process
1. **Packet Capture** → Raw packet data
2. **Feature Extractor** → Extract 20 numerical features
3. **Scaler** → Normalize features using StandardScaler
4. **Model** → Predict attack/normal classification
5. **Label Decoder** → Map prediction to class name

## Model Training

### Training Script
- **Location**: `ml_scripts/quick_train_models.py`
- **Dataset**: Synthetic data (10,000 samples)
- **Split**: 80% train, 20% test
- **Algorithm**: Random Forest with 50 estimators

### To Retrain Models
```bash
cd ml_scripts
python quick_train_models.py
```

### Advanced Training (Multiple Models)
```bash
cd ml_scripts
python train_ids_models.py
```

## Model Integration

### Python Inference
```python
import joblib
import numpy as np

# Load model and preprocessors
model = joblib.load('ml_models/random_forest_model.pkl')
scaler = joblib.load('ml_models/scaler.pkl')
label_encoder = joblib.load('ml_models/label_encoder.pkl')

# Prepare features (20 values)
features = np.array([[...]])  # Shape: (1, 20)

# Preprocess
features_scaled = scaler.transform(features)

# Predict
prediction = model.predict(features_scaled)
label = label_encoder.inverse_transform(prediction)
```

### Laravel Integration
The models are called from Laravel through:
1. **ProcessPcapFile** job → Calls `ml_scripts/predict.py`
2. **LiveMonitoringController** → Real-time packet analysis
3. **MLModelController** → Model management and metrics

## Performance Metrics

### Current Model Performance
- **Accuracy**: 99.15%
- **Inference Time**: < 10ms per prediction
- **Memory Usage**: ~50 MB when loaded
- **Batch Processing**: 1000 packets/second

### Limitations
- Binary classification only (attack/normal)
- Requires exactly 20 features
- No multi-class attack type detection yet

## Future Improvements

### Planned Enhancements
1. **Multi-class Classification**: Detect specific attack types (DoS, Probe, R2L, U2R)
2. **Deep Learning Models**: Implement LSTM/CNN for sequence analysis
3. **Online Learning**: Adapt to new attack patterns
4. **Feature Engineering**: Expand to 40+ features
5. **Real Dataset Training**: Use CIC-IDS2017 or NSL-KDD

### Model Versioning
- Current Version: 1.0.0
- Next Release: 2.0.0 (Multi-class support)
- Versioning Scheme: MAJOR.MINOR.PATCH

## Model Deployment

### Production Checklist
- [x] Model files present in `ml_models/`
- [x] Scaler and encoder saved
- [x] Metadata file with accuracy metrics
- [x] Best model indicator file
- [x] Training scripts available
- [x] Integration with Laravel backend
- [ ] API endpoint for model switching
- [ ] Model performance monitoring
- [ ] Automated retraining pipeline
