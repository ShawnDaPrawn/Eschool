// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_unavailable_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceUnavailableResponse
_$SchoolAssistanceUnavailableResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SchoolAssistanceUnavailableResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = SchoolAssistanceUnavailableResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => v == null
              ? null
              : SchoolAssistanceUnavailableResponseData.fromJson(
                  v as Map<String, dynamic>,
                ),
        ),
        code: $checkedConvert(
          'code',
          (v) => $enumDecode(
            _$ErrorCodeEnumMap,
            v,
            unknownValue: ErrorCode.unknownDefaultOpenApi,
          ),
        ),
        details: $checkedConvert('details', (v) => v as String?),
      );
      return val;
    });

Map<String, dynamic> _$SchoolAssistanceUnavailableResponseToJson(
  SchoolAssistanceUnavailableResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data?.toJson(),
  'code': _$ErrorCodeEnumMap[instance.code]!,
  'details': ?instance.details,
};

const _$ErrorCodeEnumMap = {
  ErrorCode.badRequest: 'bad_request',
  ErrorCode.unauthenticated: 'unauthenticated',
  ErrorCode.forbidden: 'forbidden',
  ErrorCode.notFound: 'not_found',
  ErrorCode.methodNotAllowed: 'method_not_allowed',
  ErrorCode.conflict: 'conflict',
  ErrorCode.payloadTooLarge: 'payload_too_large',
  ErrorCode.sessionExpired: 'session_expired',
  ErrorCode.validationFailed: 'validation_failed',
  ErrorCode.tooManyRequests: 'too_many_requests',
  ErrorCode.serverError: 'server_error',
  ErrorCode.unavailable: 'unavailable',
  ErrorCode.httpError: 'http_error',
  ErrorCode.idempotencyKeyMissing: 'idempotency_key_missing',
  ErrorCode.idempotencyKeyInvalid: 'idempotency_key_invalid',
  ErrorCode.idempotencyKeyReused: 'idempotency_key_reused',
  ErrorCode.idempotencyKeyInProgress: 'idempotency_key_in_progress',
  ErrorCode.unknownDefaultOpenApi: 'unknown_default_open_api',
};
