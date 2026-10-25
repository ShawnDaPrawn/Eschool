# Risk read-only controls

Staff follow-up screens retain authorized saved records in a read-only module and disable action, outcome, review, owner and handoff mutations. An absent optional writable capability also refuses editing. Lookup fields remain usable for authorized reads. The repository and server still authorize every request.

The focused risk screen/session/repository cohort passes 19 tests, including false and absent capabilities with saved private content retained and writes disabled. Dart analysis has no errors or warnings (one existing test brace lint was then corrected). All offered locale files receive the explicit English fallback for the new banner while existing translations are preserved. The backend read-only contract is qualified separately in #160 (17 HTTP tests / 536 assertions).
