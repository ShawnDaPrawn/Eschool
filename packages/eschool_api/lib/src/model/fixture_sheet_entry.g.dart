// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'fixture_sheet_entry.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FixtureSheetEntry _$FixtureSheetEntryFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FixtureSheetEntry', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['sheet_label', 'position']);
      final val = FixtureSheetEntry(
        sheetLabel: $checkedConvert('sheet_label', (v) => v as String),
        position: $checkedConvert('position', (v) => v as String?),
      );
      return val;
    }, fieldKeyMap: const {'sheetLabel': 'sheet_label'});

Map<String, dynamic> _$FixtureSheetEntryToJson(FixtureSheetEntry instance) =>
    <String, dynamic>{
      'sheet_label': instance.sheetLabel,
      'position': instance.position,
    };
