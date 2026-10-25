// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCard _$SecureCardFromJson(Map<String, dynamic> json) => $checkedCreate(
  'SecureCard',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'school_id',
        'person_id',
        'type',
        'status',
        'revision',
        'issued_at',
        'frozen_at',
        'replaced_by',
        'has_chip',
      ],
    );
    final val = SecureCard(
      id: $checkedConvert('id', (v) => v as String),
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      personId: $checkedConvert('person_id', (v) => (v as num).toInt()),
      type: $checkedConvert('type', (v) => v as String),
      status: $checkedConvert('status', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      issuedAt: $checkedConvert(
        'issued_at',
        (v) => DateTime.parse(v as String),
      ),
      frozenAt: $checkedConvert(
        'frozen_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      replacedBy: $checkedConvert('replaced_by', (v) => v as String?),
      hasChip: $checkedConvert('has_chip', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'personId': 'person_id',
    'issuedAt': 'issued_at',
    'frozenAt': 'frozen_at',
    'replacedBy': 'replaced_by',
    'hasChip': 'has_chip',
  },
);

Map<String, dynamic> _$SecureCardToJson(SecureCard instance) =>
    <String, dynamic>{
      'id': instance.id,
      'school_id': instance.schoolId,
      'person_id': instance.personId,
      'type': instance.type,
      'status': instance.status,
      'revision': instance.revision,
      'issued_at': instance.issuedAt.toIso8601String(),
      'frozen_at': instance.frozenAt?.toIso8601String(),
      'replaced_by': instance.replacedBy,
      'has_chip': instance.hasChip,
    };
