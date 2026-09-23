FROM python:3.12-slim AS base
WORKDIR /app
COPY requirements.txt ./
RUN pip install --no-cache-dir pytest pytest-asyncio
COPY kicc_algorithms/ ./kicc_algorithms/
COPY tests/ ./tests/
COPY examples/ ./examples/
COPY conftest.py ./
# CI gate inside the image: tests must pass before this artifact ships
RUN python -m pytest tests/ -q
CMD ["python", "examples/demo.py"]
