# Private incident photos in the apps

Identified learner/Parent reporters can select a bounded JPEG/PNG and explicitly
send it to the currently displayed report. The production file-picker helper
checks reported length before streaming, enforces the byte limit while reading,
uses a generic upload filename and asks supported picker implementations to
release transient copies. No picked path/name, report code, image or draft is
written to feature-owned disk/Hive caches. Platform picker temporary-file
behavior remains subject to physical-device qualification.

The exact bytes, operation UUID and report code remain immutable in memory for
an unknown-outcome retry. A private background or account/school/context refresh
erases the reader and pending photo. When an external picker backgrounds the app,
old bodies and codes are still erased immediately; a newly returned choice is
accepted only after fresh authority, and the reporter must reopen the intended
report before explicitly sending it. No old report is silently reattached.

Staff and reporter thumbnails use a bounded decoded image owned by the mounted
private reader, avoiding Flutter's global ImageCache. Disposal and stale decode
checks release the image handle when the reader changes. Invalid thumbnails fail
without logging source data or exposing exception details. Anonymous reporting
continues through the stateless public phone page rather than a bearer upload.

Verification: full merged shared suite 362 tests; full family suite 56 tests;
focused clinical/reporter/photo suite 41 tests, plus 2 owned-image cache/disposal
checks. Source and affected integration analysis have zero errors/warnings
(inherited style infos remain). Real Chromium ran both learner and Parent full
app profiles successfully: sign-in/home → consent/report → production picker
helper with an explicit synthetic platform-file fixture → multipart upload and
private preview → pinned account/school headers and absent private Hive values →
withdrawn resume erases body/image. This is a real app/browser run with synthetic
API/platform adapters; the separate HTTP photo suite exercises actual backend
multipart/download behavior. Neither run qualifies a physical camera, native
picker cache behavior, Xcode/store build, deployed malware scanner or production
network/performance. Staff thumbnail rendering is covered by shared code/tests;
no new native staff photo-device run is claimed.

All offered family/Teacher language catalogues contain the new labels with
English fallback where translation has not been reviewed. Existing Support,
Notes, Risk, Cover, groups and reporting routes remain in the merged tree.
Root recognition panel and its fresh group-scope correction are inherited.
