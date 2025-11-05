# ML Model Training Guide

## Overview

This guide explains how to train, evaluate, and deploy machine learning models for the IDS-IPS Dashboard.

---

## Prerequisites

### System Requirements
- Python 3.8 or higher
- 4GB RAM minimum (8GB recommended)
- 1GB free disk space

### Python Dependencies
```bash
pip install -r ml_scripts/requirements.txt
```

Required packages:
- scikit-learn
- pandas
- numpy
- joblib

---

## Quick Start Training

### Option 1: Quick Training (Recommended)

Train a basic model with synthetic data:

```bash
cd ml_scripts
python quick_train_models.py
```

**Output:**
- `ml_models/random_forest_model.pkl` - Trained Random Forest model
- `ml_models/scaler.pkl` - Feature scaler
- `ml_models/label_encoder.pkl` - Label encoder
- `ml_models/model_metadata.json` - Training metrics

**Training Time:** ~30 seconds  
**Expected Accuracy:** 95%+

### Option 2: Advanced Training

Train multiple models with more features:

```bash
cd ml_scripts
python train_ids_models.py
```

This trains:
- Random Forest
- Gradient Boosting
- Neural Network

**Training Time:** ~5-10 minutes  
**Expected Accuracy:** 90%+

---

## Using Custom Datasets

### Dataset Format

The training script accepts CSV files with the following structure:

```csv
duration,protocol_type,src_bytes,dst_bytes,...,attack_type
0.5,tcp,100,200,...,normal
1.2,udp,300,150,...,dos
```

### Required Features

Minimum 20 features:
1. Basic flow features (duration, bytes, packets)
2. Protocol information (TCP/UDP/ICMP)
3. Port numbers (source and destination)
4. TCP flags (SYN, ACK, FIN, RST, etc.)
5. Connection statistics

### Training with Custom Data

```python
# In train_model.py
python train_model.py /path/to/dataset.csv ml_models/

# Or modify the script to load your data:
def load_custom_data(path):
    df = pd.read_csv(path)
    # Your preprocessing logic
    return X, y
```

---

## Using Public Datasets

### CIC-IDS2017

Popular dataset for IDS training:

```bash
# Download dataset
wget https://...cic-ids-2017.csv

# Train model
python ml_scripts/train_ids_models.py cic-ids-2017.csv
```

### NSL-KDD

Classic IDS dataset:

```bash
# Use NSL-KDD dataset
python ml_scripts/train_ids_models.py nsl-kdd-train.csv
```

---

## Model Evaluation

### Metrics to Monitor

After training, check these metrics in `model_metadata.json`:

```json
{
  "accuracy": 0.9915,
  "precision": 0.99,
  "recall": 0.99,
  "f1_score": 0.99,
  "confusion_matrix": [[...]]
}
```

### Good Model Criteria
- **Accuracy**: > 90%
- **Precision**: > 85% (minimize false positives)
- **Recall**: > 90% (catch most attacks)
- **F1-Score**: > 88% (balanced performance)

### Evaluate Trained Model

```python
from ml_scripts.evaluate_model import evaluate

# Test on validation set
results = evaluate('ml_models/random_forest_model.pkl', 'test_data.csv')
print(results)
```

---

## Feature Engineering

### Standard Features (20)

```python
features = [
    'packet_length',      # Size of packet
    'protocol',           # TCP=6, UDP=17, ICMP=1
    'src_port',           # Source port number
    'dst_port',           # Destination port
    'flags_syn',          # SYN flag present
    'flags_ack',          # ACK flag present
    'flags_fin',          # FIN flag present
    'flags_rst',          # RST flag present
    'flags_psh',          # PSH flag present
    'flags_urg',          # URG flag present
    'ttl',                # Time to live
    'window_size',        # TCP window size
    'payload_size',       # Payload length
    'tcp_seq',            # Sequence number
    'tcp_ack',            # Acknowledgment number
    'ip_id',              # IP identification
    'fragment_offset',    # Fragment offset
    'checksum',           # Packet checksum
    'options_length',     # Options field length
    'reserved',           # Reserved field
]
```

### Extracting Features from PCAP

```python
from scapy.all import rdpcap

def extract_features(pcap_file):
    packets = rdpcap(pcap_file)
    features = []
    
    for packet in packets:
        feature_vector = [
            len(packet),
            packet[IP].proto if IP in packet else 0,
            packet[TCP].sport if TCP in packet else 0,
            # ... extract all 20 features
        ]
        features.append(feature_vector)
    
    return np.array(features)
```

---

## Model Versioning

### Version Naming Convention

```
model_name_v{major}.{minor}.{patch}.pkl

Examples:
- random_forest_v1.0.0.pkl
- gradient_boost_v1.1.0.pkl
- neural_network_v2.0.0.pkl
```

### Version Changes

- **Major**: Algorithm change or complete retraining
- **Minor**: Feature additions or significant improvements
- **Patch**: Bug fixes or small optimizations

### Tracking Versions

```bash
# Tag model versions in git
git tag -a model-v1.0.0 -m "Initial Random Forest model"
git push origin model-v1.0.0
```

---

## Model Deployment

### Step 1: Train and Validate

```bash
python ml_scripts/quick_train_models.py
python ml_scripts/test_prediction.py  # Verify predictions work
```

### Step 2: Copy to Production

```bash
# Backup old models
cp -r ml_models ml_models.backup

# Deploy new models
cp ml_models_new/* ml_models/
```

### Step 3: Update Configuration

```env
ML_MODEL_VERSION=1.1.0
ML_ACTIVE_MODEL=random_forest_model.pkl
```

### Step 4: Test Integration

```bash
# Test prediction API
php artisan ml:test-prediction

# Restart services
php artisan queue:restart
```

---

## Continuous Training

### Automated Retraining

Setup a cron job to retrain models periodically:

```bash
# crontab -e
0 2 * * 0 cd /path/to/project && python ml_scripts/train_ids_models.py >> logs/training.log 2>&1
```

### Monitoring Model Performance

Track these metrics over time:
- Prediction accuracy
- False positive rate
- False negative rate
- Inference time

```php
// In Laravel
$metrics = Alert::whereDate('created_at', today())
    ->selectRaw('
        COUNT(*) as total,
        SUM(CASE WHEN verified = true THEN 1 ELSE 0 END) as true_positives,
        AVG(confidence) as avg_confidence
    ')
    ->first();
```

---

## Troubleshooting

### Model Not Loading

**Error:** "Failed to load models"

**Solution:**
```bash
# Check if model files exist
ls -lh ml_models/

# Verify file permissions
chmod 644 ml_models/*.pkl

# Test Python can load them
python -c "import joblib; joblib.load('ml_models/random_forest_model.pkl')"
```

### Low Accuracy

**Solutions:**
1. **More Training Data**: Use larger dataset
2. **Feature Engineering**: Add more features
3. **Hyperparameter Tuning**: Adjust model parameters
4. **Ensemble Methods**: Combine multiple models

### Slow Predictions

**Solutions:**
1. Use smaller model (fewer estimators)
2. Enable batch processing
3. Add caching for common patterns
4. Use GPU acceleration

---

## Advanced Topics

### Hyperparameter Tuning

```python
from sklearn.model_selection import GridSearchCV

param_grid = {
    'n_estimators': [50, 100, 200],
    'max_depth': [10, 20, 30],
    'min_samples_split': [2, 5, 10]
}

grid_search = GridSearchCV(
    RandomForestClassifier(),
    param_grid,
    cv=5,
    n_jobs=-1
)

grid_search.fit(X_train, y_train)
best_model = grid_search.best_estimator_
```

### Feature Importance

```python
import matplotlib.pyplot as plt

# Get feature importance
importances = model.feature_importances_
indices = np.argsort(importances)[::-1]

# Plot
plt.figure(figsize=(10, 6))
plt.bar(range(20), importances[indices])
plt.title('Feature Importances')
plt.savefig('feature_importance.png')
```

### Model Explainability

```python
from sklearn.inspection import permutation_importance

# Calculate permutation importance
result = permutation_importance(model, X_test, y_test, n_repeats=10)

# Show top features
for i in result.importances_mean.argsort()[::-1][:10]:
    print(f"{feature_names[i]}: {result.importances_mean[i]:.3f}")
```

---

## Resources

### Documentation
- [Scikit-learn Documentation](https://scikit-learn.org/)
- [IDS/IPS Best Practices](https://...)
- [CIC-IDS2017 Dataset](https://...)

### Related Scripts
- `ml_scripts/quick_train_models.py` - Fast training
- `ml_scripts/train_ids_models.py` - Advanced training
- `ml_scripts/predict_packet.py` - Prediction script
- `ml_scripts/evaluate_model.py` - Model evaluation

### Support
- GitHub Issues: https://github.com/MO-HS7/IDS-IPS-Dashboard/issues
- Documentation: `/docs`

---

## Best Practices

1. **Always validate before deployment**
2. **Keep training data separate from test data**
3. **Version control your models**
4. **Monitor performance in production**
5. **Retrain periodically with new data**
6. **Document feature engineering**
7. **Test predictions before going live**
8. **Backup models before updates**

---

**Last Updated:** 2025-11-05  
**Model Version:** 1.0.0  
**Training Framework:** scikit-learn 1.3+
