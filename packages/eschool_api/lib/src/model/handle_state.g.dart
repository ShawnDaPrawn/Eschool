// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'handle_state.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

HandleState _$HandleStateFromJson(Map<String, dynamic> json) =>
    $checkedCreate('HandleState', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['handle', 'proposal', 'can_change_after'],
      );
      final val = HandleState(
        handle: $checkedConvert('handle', (v) => v as String?),
        proposal: $checkedConvert(
          'proposal',
          (v) => v == null
              ? null
              : HandleProposal.fromJson(v as Map<String, dynamic>),
        ),
        canChangeAfter: $checkedConvert(
          'can_change_after',
          (v) => v == null ? null : DateTime.parse(v as String),
        ),
      );
      return val;
    }, fieldKeyMap: const {'canChangeAfter': 'can_change_after'});

Map<String, dynamic> _$HandleStateToJson(HandleState instance) =>
    <String, dynamic>{
      'handle': instance.handle,
      'proposal': instance.proposal?.toJson(),
      'can_change_after': instance.canChangeAfter?.toIso8601String(),
    };
