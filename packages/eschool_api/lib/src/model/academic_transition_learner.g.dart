// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_learner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionLearner _$AcademicTransitionLearnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicTransitionLearner',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'name',
        'student_number',
        'state',
        'page',
        'last_page',
        'transitions',
        'can_export',
        'report_choices_truncated',
        'report_choices',
        'document_choices',
        'exports',
      ],
    );
    final val = AcademicTransitionLearner(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      studentNumber: $checkedConvert('student_number', (v) => v as String?),
      state: $checkedConvert(
        'state',
        (v) => AcademicTransitionState.fromJson(v as Map<String, dynamic>),
      ),
      page: $checkedConvert('page', (v) => (v as num).toInt()),
      lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      transitions: $checkedConvert(
        'transitions',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  AcademicTransitionRecord.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      canExport: $checkedConvert('can_export', (v) => v as bool),
      reportChoicesTruncated: $checkedConvert(
        'report_choices_truncated',
        (v) => v as bool,
      ),
      reportChoices: $checkedConvert(
        'report_choices',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  AcademicExitReportChoice.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      documentChoices: $checkedConvert(
        'document_choices',
        (v) => (v as List<dynamic>)
            .map(
              (e) => AcademicExitDocumentChoice.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      exports: $checkedConvert(
        'exports',
        (v) => (v as List<dynamic>)
            .map((e) => AcademicExitPackage.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'studentNumber': 'student_number',
    'lastPage': 'last_page',
    'canExport': 'can_export',
    'reportChoicesTruncated': 'report_choices_truncated',
    'reportChoices': 'report_choices',
    'documentChoices': 'document_choices',
  },
);

Map<String, dynamic> _$AcademicTransitionLearnerToJson(
  AcademicTransitionLearner instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'student_number': instance.studentNumber,
  'state': instance.state.toJson(),
  'page': instance.page,
  'last_page': instance.lastPage,
  'transitions': instance.transitions.map((e) => e.toJson()).toList(),
  'can_export': instance.canExport,
  'report_choices_truncated': instance.reportChoicesTruncated,
  'report_choices': instance.reportChoices.map((e) => e.toJson()).toList(),
  'document_choices': instance.documentChoices.map((e) => e.toJson()).toList(),
  'exports': instance.exports.map((e) => e.toJson()).toList(),
};
