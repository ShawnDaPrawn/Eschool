// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'facilitator_policy.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FacilitatorPolicy _$FacilitatorPolicyFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FacilitatorPolicy', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['document', 'version', 'title', 'text'],
      );
      final val = FacilitatorPolicy(
        document: $checkedConvert(
          'document',
          (v) => $enumDecode(
            _$FacilitatorPolicyDocumentEnumEnumMap,
            v,
            unknownValue: FacilitatorPolicyDocumentEnum.unknownDefaultOpenApi,
          ),
        ),
        version: $checkedConvert('version', (v) => v as String),
        title: $checkedConvert('title', (v) => v as String),
        text: $checkedConvert('text', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$FacilitatorPolicyToJson(FacilitatorPolicy instance) =>
    <String, dynamic>{
      'document': _$FacilitatorPolicyDocumentEnumEnumMap[instance.document]!,
      'version': instance.version,
      'title': instance.title,
      'text': instance.text,
    };

const _$FacilitatorPolicyDocumentEnumEnumMap = {
  FacilitatorPolicyDocumentEnum.navigateed: 'navigateed',
  FacilitatorPolicyDocumentEnum.school: 'school',
  FacilitatorPolicyDocumentEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
