// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_gate_lookup.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyGateLookup _$FamilyGateLookupFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'FamilyGateLookup',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'available',
            'scan_id',
            'learner_id',
            'learner_name',
            'passes',
            'collector_check_required',
          ],
        );
        final val = FamilyGateLookup(
          available: $checkedConvert('available', (v) => v as bool),
          scanId: $checkedConvert('scan_id', (v) => v as String),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          learnerName: $checkedConvert('learner_name', (v) => v as String),
          passes: $checkedConvert(
            'passes',
            (v) => (v as List<dynamic>)
                .map(
                  (e) =>
                      FamilyGateClearance.fromJson(e as Map<String, dynamic>),
                )
                .toList(),
          ),
          collectorCheckRequired: $checkedConvert(
            'collector_check_required',
            (v) => v as bool,
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'scanId': 'scan_id',
        'learnerId': 'learner_id',
        'learnerName': 'learner_name',
        'collectorCheckRequired': 'collector_check_required',
      },
    );

Map<String, dynamic> _$FamilyGateLookupToJson(FamilyGateLookup instance) =>
    <String, dynamic>{
      'available': instance.available,
      'scan_id': instance.scanId,
      'learner_id': instance.learnerId,
      'learner_name': instance.learnerName,
      'passes': instance.passes.map((e) => e.toJson()).toList(),
      'collector_check_required': instance.collectorCheckRequired,
    };
