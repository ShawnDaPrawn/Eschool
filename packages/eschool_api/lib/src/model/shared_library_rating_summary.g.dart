// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_rating_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryRatingSummary _$SharedLibraryRatingSummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SharedLibraryRatingSummary', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['count', 'average']);
  final val = SharedLibraryRatingSummary(
    count: $checkedConvert('count', (v) => (v as num).toInt()),
    average: $checkedConvert('average', (v) => v as num),
  );
  return val;
});

Map<String, dynamic> _$SharedLibraryRatingSummaryToJson(
  SharedLibraryRatingSummary instance,
) => <String, dynamic>{'count': instance.count, 'average': instance.average};
