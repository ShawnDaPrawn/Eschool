# Live smoke platform collection

The two real socket browser flows require the loopback Reverb server and signed
synthetic publisher wired into the Chrome job. Android's broad integration-test
collection had also collected them without either fixture or adb port reversal.

Android now excludes exactly facilitator_live_smoke_test.dart and
family_live_recognition_smoke_test.dart. Its role/auth/fake-API flows remain
selected. Both real live flows remain in Chrome with PHP, pinned Reverb, signed
publisher setup and always-run cleanup. Source collection was evaluated against
all three Android rows, and both Chrome entries/setup/cleanup were checked.
Native IO WebSocket protocol tests remain separately available; an actual
Reverb-to-Android application journey has not been qualified.
