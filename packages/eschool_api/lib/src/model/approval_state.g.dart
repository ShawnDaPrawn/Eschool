// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'approval_state.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ApprovalState _$ApprovalStateFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ApprovalState', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['status', 'approved_by_count']);
      final val = ApprovalState(
        status: $checkedConvert(
          'status',
          (v) => $enumDecode(
            _$ApprovalStateStatusEnumEnumMap,
            v,
            unknownValue: ApprovalStateStatusEnum.unknownDefaultOpenApi,
          ),
        ),
        approvedByCount: $checkedConvert(
          'approved_by_count',
          (v) => (v as num).toInt(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'approvedByCount': 'approved_by_count'});

Map<String, dynamic> _$ApprovalStateToJson(ApprovalState instance) =>
    <String, dynamic>{
      'status': _$ApprovalStateStatusEnumEnumMap[instance.status]!,
      'approved_by_count': instance.approvedByCount,
    };

const _$ApprovalStateStatusEnumEnumMap = {
  ApprovalStateStatusEnum.pending: 'pending',
  ApprovalStateStatusEnum.approved: 'approved',
  ApprovalStateStatusEnum.withdrawn: 'withdrawn',
  ApprovalStateStatusEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
