// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'handle_proposal.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

HandleProposal _$HandleProposalFromJson(Map<String, dynamic> json) =>
    $checkedCreate('HandleProposal', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['id', 'handle', 'status', 'expires_at'],
      );
      final val = HandleProposal(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        handle: $checkedConvert('handle', (v) => v as String),
        status: $checkedConvert('status', (v) => v as String),
        expiresAt: $checkedConvert(
          'expires_at',
          (v) => DateTime.parse(v as String),
        ),
      );
      return val;
    }, fieldKeyMap: const {'expiresAt': 'expires_at'});

Map<String, dynamic> _$HandleProposalToJson(HandleProposal instance) =>
    <String, dynamic>{
      'id': instance.id,
      'handle': instance.handle,
      'status': instance.status,
      'expires_at': instance.expiresAt.toIso8601String(),
    };
