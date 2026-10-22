//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

/// What went wrong (see the table in the description). Clients must accept codes they do not know.
enum ErrorCode {
  @JsonValue(r'bad_request')
  badRequest(r'bad_request'),
  @JsonValue(r'unauthenticated')
  unauthenticated(r'unauthenticated'),
  @JsonValue(r'forbidden')
  forbidden(r'forbidden'),
  @JsonValue(r'not_found')
  notFound(r'not_found'),
  @JsonValue(r'method_not_allowed')
  methodNotAllowed(r'method_not_allowed'),
  @JsonValue(r'conflict')
  conflict(r'conflict'),
  @JsonValue(r'payload_too_large')
  payloadTooLarge(r'payload_too_large'),
  @JsonValue(r'session_expired')
  sessionExpired(r'session_expired'),
  @JsonValue(r'validation_failed')
  validationFailed(r'validation_failed'),
  @JsonValue(r'too_many_requests')
  tooManyRequests(r'too_many_requests'),
  @JsonValue(r'server_error')
  serverError(r'server_error'),
  @JsonValue(r'unavailable')
  unavailable(r'unavailable'),
  @JsonValue(r'http_error')
  httpError(r'http_error'),
  @JsonValue(r'idempotency_key_missing')
  idempotencyKeyMissing(r'idempotency_key_missing'),
  @JsonValue(r'idempotency_key_invalid')
  idempotencyKeyInvalid(r'idempotency_key_invalid'),
  @JsonValue(r'idempotency_key_reused')
  idempotencyKeyReused(r'idempotency_key_reused'),
  @JsonValue(r'idempotency_key_in_progress')
  idempotencyKeyInProgress(r'idempotency_key_in_progress'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ErrorCode(this.value);

  final String value;

  @override
  String toString() => value;
}
