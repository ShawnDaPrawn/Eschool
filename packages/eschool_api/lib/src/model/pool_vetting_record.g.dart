// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_vetting_record.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolVettingRecord _$PoolVettingRecordFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PoolVettingRecord',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'outcome',
            'checks',
            'checked_by',
            'checked_on',
            'expires_on',
            'note',
          ],
        );
        final val = PoolVettingRecord(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          outcome: $checkedConvert(
            'outcome',
            (v) => $enumDecode(
              _$PoolVettingRecordOutcomeEnumEnumMap,
              v,
              unknownValue: PoolVettingRecordOutcomeEnum.unknownDefaultOpenApi,
            ),
          ),
          checks: $checkedConvert(
            'checks',
            (v) => (v as List<dynamic>).map((e) => e as String).toList(),
          ),
          checkedBy: $checkedConvert('checked_by', (v) => (v as num).toInt()),
          checkedOn: $checkedConvert('checked_on', (v) => v as String),
          expiresOn: $checkedConvert('expires_on', (v) => v as String?),
          note: $checkedConvert('note', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {
        'checkedBy': 'checked_by',
        'checkedOn': 'checked_on',
        'expiresOn': 'expires_on',
      },
    );

Map<String, dynamic> _$PoolVettingRecordToJson(PoolVettingRecord instance) =>
    <String, dynamic>{
      'id': instance.id,
      'outcome': _$PoolVettingRecordOutcomeEnumEnumMap[instance.outcome]!,
      'checks': instance.checks,
      'checked_by': instance.checkedBy,
      'checked_on': instance.checkedOn,
      'expires_on': instance.expiresOn,
      'note': instance.note,
    };

const _$PoolVettingRecordOutcomeEnumEnumMap = {
  PoolVettingRecordOutcomeEnum.cleared: 'cleared',
  PoolVettingRecordOutcomeEnum.refused: 'refused',
  PoolVettingRecordOutcomeEnum.revoked: 'revoked',
  PoolVettingRecordOutcomeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
