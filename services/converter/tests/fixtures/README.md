# Synthetic speech fixture

`fractions.wav.gz.b64` is a gzip/base64-encoded WAV generated locally with
espeak-ng from this repository-owned test sentence:

> One half and one quarter are fractions. Two quarters make one half. This lesson
> explains equivalent fractions.

No child, teacher, external recording or private document is included. The test
creates MP4 video locally from this audio. Model weights are never committed;
`tool/download_model.py` fetches the reviewed public revision and verifies its
recorded hashes. Tests use only the offline model and never send media to a provider.
