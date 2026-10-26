# Bounded photographed-working derivatives

The old 32 KiB PNG limit rejected a deterministic photographed-paper fixture
with ordinary shading, RGB sensor variation and working strokes. Its original
fits the existing 4 MiB upload bound; the normal 640-by-768 derivative is about
684 KiB. The approved derivative now has a 1 MiB bound, with dimensions,
PNG chunk types/length/CRC, hash, source authorization and explicit crop approval
still checked. Per-image token reservation stays 8,192. Text-only requests keep
the 256 KiB transport limit; only explicit vision requests/moderation can use
5 MiB for at most three bounded images. Responses retain their 1 MiB limit.

PrivateReportImage keeps its 524,288-character encoded default and 360px default.
Tutor explicitly opts into maximumEncodedLength: 1398104 and its existing 768px
edge. The shared PNG/JPEG header parser validates decoded-byte length, dimensions
and pixel count before invoking the image codec, avoiding a large raster
allocation before refusal. The existing tutorPhotoDimensions entrypoint remains.
Frames remain reader-owned, generation-bound and outside the global ImageCache.
TutorWorkingCrop additionally accepts selectionTitle, selectionGuidance and
approvalLabel with the exact existing defaults, allowing teacher-specific copy.

Evidence: 24 backend/protocol cases / 195 assertions pass, including the real-paper
normalization through moderated/metered inference, three large derivatives,
existing metadata/CRC/source guards, bounded requests and unchanged token
allowance. The 25 focused shared widget/repository/context cases pass, including
large preview opt-in, oversized raster-header refusal, frame disposal, exact
intent recovery, role withdrawal and custom crop copy. Selected PHPStan and Dart
analysis have zero errors/warnings; owned formatting passes. No dependency
version or existing consumer default was changed.

The photographic fixture and provider replies are synthetic. No live-provider
latency, physical camera or universal visible-identifier detection is claimed;
all external inference remains disabled pending its recorded qualification.
