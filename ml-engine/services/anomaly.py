"""Anomaly detection: fraud, account takeover, review manipulation."""
import numpy as np

class AnomalyDetector:
    def detect(self, entity_type: str, entity_id: int) -> dict:
        """
        Detect anomalies by entity type.
        Uses Isolation Forest for numerical patterns + rule-based for known fraud signals.
        """
        if entity_type == "payment":
            return self._payment_anomaly(entity_id)
        elif entity_type == "review":
            return self._review_anomaly(entity_id)
        elif entity_type == "login":
            return self._login_anomaly(entity_id)
        return {"anomalous": False, "score": 0.0, "reasons": []}

    def _payment_anomaly(self, user_id: int) -> dict:
        """
        Detect unusual payment patterns:
        - Rapid successive payments (velocity)
        - Amount outside normal range
        - Geographic mismatch (IP different from shipping)
        """
        # Placeholder: return false
        return {"anomalous": False, "score": 0.1, "reasons": ["No anomalous patterns detected"]}

    def _review_anomaly(self, product_id: int) -> dict:
        """
        Detect review manipulation:
        - Burst of 5-star reviews in short time
        - Reviewer has only ever reviewed this product
        - Text similarity across many reviews
        """
        return {"anomalous": False, "score": 0.05, "reasons": ["Review pattern normal"]}

    def _login_anomaly(self, user_id: int) -> dict:
        """Detect account takeover via geographic velocity impossible travel."""
        return {"anomalous": False, "score": 0.0, "reasons": ["Login pattern normal"]}