// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_offline_snapshot_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureOfflineSnapshotResponse _$SecureOfflineSnapshotResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SecureOfflineSnapshotResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SecureOfflineSnapshotResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SecureOfflineSnapshot.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$SecureOfflineSnapshotResponseCodeEnumEnumMap,
        v,
        unknownValue:
            SecureOfflineSnapshotResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$SecureOfflineSnapshotResponseToJson(
  SecureOfflineSnapshotResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$SecureOfflineSnapshotResponseCodeEnumEnumMap[instance.code]!,
};

const _$SecureOfflineSnapshotResponseCodeEnumEnumMap = {
  SecureOfflineSnapshotResponseCodeEnum.number200: 200,
  SecureOfflineSnapshotResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
