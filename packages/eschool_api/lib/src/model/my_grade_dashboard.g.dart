// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'my_grade_dashboard.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MyGradeDashboard _$MyGradeDashboardFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'MyGradeDashboard',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'school_id',
            'reader_id',
            'session_year_id',
            'grade_level_id',
            'on_date',
            'writable',
            'page',
            'last_page',
            'total',
            'risk_available',
            'learners',
            'discipline',
            'awards',
            'actions',
          ],
        );
        final val = MyGradeDashboard(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          gradeLevelId: $checkedConvert(
            'grade_level_id',
            (v) => (v as num).toInt(),
          ),
          onDate: $checkedConvert('on_date', (v) => v as String),
          writable: $checkedConvert('writable', (v) => v as bool),
          page: $checkedConvert('page', (v) => (v as num).toInt()),
          lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
          total: $checkedConvert('total', (v) => (v as num).toInt()),
          riskAvailable: $checkedConvert('risk_available', (v) => v as bool),
          learners: $checkedConvert(
            'learners',
            (v) => (v as List<dynamic>)
                .map((e) => MyGradeLearner.fromJson(e as Map<String, dynamic>))
                .toList(),
          ),
          discipline: $checkedConvert(
            'discipline',
            (v) => MyGradeDisciplineQueue.fromJson(v as Map<String, dynamic>),
          ),
          awards: $checkedConvert(
            'awards',
            (v) => MyGradeAwards.fromJson(v as Map<String, dynamic>),
          ),
          actions: $checkedConvert(
            'actions',
            (v) => MyGradeActions.fromJson(v as Map<String, dynamic>),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'schoolId': 'school_id',
        'readerId': 'reader_id',
        'sessionYearId': 'session_year_id',
        'gradeLevelId': 'grade_level_id',
        'onDate': 'on_date',
        'lastPage': 'last_page',
        'riskAvailable': 'risk_available',
      },
    );

Map<String, dynamic> _$MyGradeDashboardToJson(MyGradeDashboard instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'session_year_id': instance.sessionYearId,
      'grade_level_id': instance.gradeLevelId,
      'on_date': instance.onDate,
      'writable': instance.writable,
      'page': instance.page,
      'last_page': instance.lastPage,
      'total': instance.total,
      'risk_available': instance.riskAvailable,
      'learners': instance.learners.map((e) => e.toJson()).toList(),
      'discipline': instance.discipline.toJson(),
      'awards': instance.awards.toJson(),
      'actions': instance.actions.toJson(),
    };
