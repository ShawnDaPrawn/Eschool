# Generic profile preview browser correction

The private profile image viewer now owns a codec/RawImage and releases its bytes
and decoded image instead of adding a MemoryImage to the global cache. The old
browser smoke predicate waited for that removed ImageProvider implementation.

This test-only correction waits for the actual decoded RawImage and checks both
1×1 dimensions, no global ImageCache addition, and image disposal after Back.
Authenticated bearer/school headers, trusted endpoint host, retained unrelated
profile drafts, exact partial save and authenticated PDF readiness remain tested.

Actual Chromium profile_smoke_test.dart passes both scenarios on the #258
8ea191ce tree plus this test correction. Targeted source analysis is clean. No
production renderer, authorization, timeout or CI requirement changed.
