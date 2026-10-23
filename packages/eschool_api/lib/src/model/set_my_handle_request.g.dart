// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'set_my_handle_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SetMyHandleRequest _$SetMyHandleRequestFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SetMyHandleRequest', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['handle']);
      final val = SetMyHandleRequest(
        handle: $checkedConvert('handle', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$SetMyHandleRequestToJson(SetMyHandleRequest instance) =>
    <String, dynamic>{'handle': instance.handle};
