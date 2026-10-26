// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_run_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceRunRequest _$SchoolAssistanceRunRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolAssistanceRunRequest', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['identifiers_reviewed']);
  final val = SchoolAssistanceRunRequest(
    identifiersReviewed: $checkedConvert(
      'identifiers_reviewed',
      (v) => v as bool,
    ),
  );
  return val;
}, fieldKeyMap: const {'identifiersReviewed': 'identifiers_reviewed'});

Map<String, dynamic> _$SchoolAssistanceRunRequestToJson(
  SchoolAssistanceRunRequest instance,
) => <String, dynamic>{'identifiers_reviewed': instance.identifiersReviewed};
