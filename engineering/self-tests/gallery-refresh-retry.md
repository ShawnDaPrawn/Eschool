# Gallery refresh retry correction

An upload whose response is lost retains its bounded in-memory operation ID,
private caption and pixels while a foreground refresh fails transiently. A
transient read failure clears all rendered photographs and editable drafts but
cannot prove that an earlier dispatched mutation failed. A definite refusal,
account replacement or screen disposal still erases the retained intent.

Regression verification uses the shared gallery widget suite: a dispatched
upload loses its response, the app backgrounds, its configuration refresh fails,
and the explicit retry sends the identical operation and pixel bytes. The same
sequence covers a tag removal. There is no automatic mutation retry or disk cache.
