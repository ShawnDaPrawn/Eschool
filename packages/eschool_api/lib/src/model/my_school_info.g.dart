// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'my_school_info.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MySchoolInfo _$MySchoolInfoFromJson(Map<String, dynamic> json) =>
    $checkedCreate('MySchoolInfo', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const [
          'website',
          'phone',
          'email',
          'address',
          'documents',
          'term_dates',
        ],
      );
      final val = MySchoolInfo(
        school: $checkedConvert(
          'school',
          (v) => v == null
              ? null
              : SchoolSummary.fromJson(v as Map<String, dynamic>),
        ),
        website: $checkedConvert('website', (v) => v as String?),
        phone: $checkedConvert('phone', (v) => v as String?),
        email: $checkedConvert('email', (v) => v as String?),
        address: $checkedConvert('address', (v) => v as String?),
        documents: $checkedConvert(
          'documents',
          (v) => (v as List<dynamic>)
              .map((e) => SchoolDocument.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
        termDates: $checkedConvert(
          'term_dates',
          (v) => (v as List<dynamic>)
              .map((e) => TermDates.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'termDates': 'term_dates'});

Map<String, dynamic> _$MySchoolInfoToJson(MySchoolInfo instance) =>
    <String, dynamic>{
      'school': ?instance.school?.toJson(),
      'website': instance.website,
      'phone': instance.phone,
      'email': instance.email,
      'address': instance.address,
      'documents': instance.documents.map((e) => e.toJson()).toList(),
      'term_dates': instance.termDates.map((e) => e.toJson()).toList(),
    };
