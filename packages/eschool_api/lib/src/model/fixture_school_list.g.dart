// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'fixture_school_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FixtureSchoolList _$FixtureSchoolListFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FixtureSchoolList', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['items']);
      final val = FixtureSchoolList(
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map(
                (e) => GuardianInvitationPreviewLearnersInner.fromJson(
                  e as Map<String, dynamic>,
                ),
              )
              .toList(),
        ),
      );
      return val;
    });

Map<String, dynamic> _$FixtureSchoolListToJson(FixtureSchoolList instance) =>
    <String, dynamic>{'items': instance.items.map((e) => e.toJson()).toList()};
