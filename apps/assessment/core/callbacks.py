import hashlib
import hmac
import json
import logging
import time
import urllib.request

from django.conf import settings

logger = logging.getLogger(__name__)


def sign(body: bytes, timestamp: str) -> str:
    message = timestamp.encode() + b"." + body
    return hmac.new(settings.SERVICE_JWT["SECRET"].encode(), message, hashlib.sha256).hexdigest()


def notify_core(event: str, payload: dict) -> None:
    """Kirim webhook ke Laravel. Gagal kirim tidak menggagalkan job: Laravel juga bisa polling status."""
    if not settings.CORE_CALLBACK_URL:
        return

    body = json.dumps({"event": event, "data": payload}).encode()
    timestamp = str(int(time.time()))
    request = urllib.request.Request(
        settings.CORE_CALLBACK_URL,
        data=body,
        method="POST",
        headers={
            "Content-Type": "application/json",
            "X-EduERP-Timestamp": timestamp,
            "X-EduERP-Signature": sign(body, timestamp),
        },
    )
    try:
        urllib.request.urlopen(request, timeout=10).close()
    except OSError:
        logger.warning("Callback %s ke core gagal", event, exc_info=True)
