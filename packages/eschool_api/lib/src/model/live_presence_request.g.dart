// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'live_presence_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LivePresenceRequest _$LivePresenceRequestFromJson(Map<String, dynamic> json) =>
    $checkedCreate('LivePresenceRequest', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['lease_id']);
      final val = LivePresenceRequest(
        leaseId: $checkedConvert('lease_id', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'leaseId': 'lease_id'});

Map<String, dynamic> _$LivePresenceRequestToJson(
  LivePresenceRequest instance,
) => <String, dynamic>{'lease_id': instance.leaseId};
