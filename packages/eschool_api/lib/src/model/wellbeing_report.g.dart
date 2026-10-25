// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_report.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingReport _$WellbeingReportFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'WellbeingReport',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'category_id',
            'anonymous',
            'reporter_user_id',
            'learner_id',
            'body',
            'urgency',
            'status',
            'identified_at',
            'revision',
            'created_at',
            'threads',
            'category_name',
            'reporter_name',
            'learner_name',
          ],
        );
        final val = WellbeingReport(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          categoryId: $checkedConvert('category_id', (v) => (v as num).toInt()),
          anonymous: $checkedConvert('anonymous', (v) => v as bool),
          reporterUserId: $checkedConvert(
            'reporter_user_id',
            (v) => (v as num?)?.toInt(),
          ),
          learnerId: $checkedConvert('learner_id', (v) => (v as num?)?.toInt()),
          body: $checkedConvert('body', (v) => v as String),
          urgency: $checkedConvert(
            'urgency',
            (v) => $enumDecode(
              _$WellbeingReportUrgencyEnumEnumMap,
              v,
              unknownValue: WellbeingReportUrgencyEnum.unknownDefaultOpenApi,
            ),
          ),
          status: $checkedConvert(
            'status',
            (v) => $enumDecode(
              _$WellbeingReportStatusEnumEnumMap,
              v,
              unknownValue: WellbeingReportStatusEnum.unknownDefaultOpenApi,
            ),
          ),
          identifiedAt: $checkedConvert(
            'identified_at',
            (v) => v == null ? null : DateTime.parse(v as String),
          ),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          createdAt: $checkedConvert(
            'created_at',
            (v) => DateTime.parse(v as String),
          ),
          threads: $checkedConvert(
            'threads',
            (v) => (v as List<dynamic>)
                .map(
                  (e) =>
                      WellbeingReportThread.fromJson(e as Map<String, dynamic>),
                )
                .toList(),
          ),
          categoryName: $checkedConvert('category_name', (v) => v as String),
          reporterName: $checkedConvert('reporter_name', (v) => v as String?),
          learnerName: $checkedConvert('learner_name', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {
        'categoryId': 'category_id',
        'reporterUserId': 'reporter_user_id',
        'learnerId': 'learner_id',
        'identifiedAt': 'identified_at',
        'createdAt': 'created_at',
        'categoryName': 'category_name',
        'reporterName': 'reporter_name',
        'learnerName': 'learner_name',
      },
    );

Map<String, dynamic> _$WellbeingReportToJson(WellbeingReport instance) =>
    <String, dynamic>{
      'id': instance.id,
      'category_id': instance.categoryId,
      'anonymous': instance.anonymous,
      'reporter_user_id': instance.reporterUserId,
      'learner_id': instance.learnerId,
      'body': instance.body,
      'urgency': _$WellbeingReportUrgencyEnumEnumMap[instance.urgency]!,
      'status': _$WellbeingReportStatusEnumEnumMap[instance.status]!,
      'identified_at': instance.identifiedAt?.toIso8601String(),
      'revision': instance.revision,
      'created_at': instance.createdAt.toIso8601String(),
      'threads': instance.threads.map((e) => e.toJson()).toList(),
      'category_name': instance.categoryName,
      'reporter_name': instance.reporterName,
      'learner_name': instance.learnerName,
    };

const _$WellbeingReportUrgencyEnumEnumMap = {
  WellbeingReportUrgencyEnum.routine: 'routine',
  WellbeingReportUrgencyEnum.urgent: 'urgent',
  WellbeingReportUrgencyEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$WellbeingReportStatusEnumEnumMap = {
  WellbeingReportStatusEnum.open: 'open',
  WellbeingReportStatusEnum.triaged: 'triaged',
  WellbeingReportStatusEnum.resolved: 'resolved',
  WellbeingReportStatusEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
