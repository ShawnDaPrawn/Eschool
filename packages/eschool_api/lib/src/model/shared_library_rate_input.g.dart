// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_rate_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryRateInput _$SharedLibraryRateInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SharedLibraryRateInput', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['stars']);
  final val = SharedLibraryRateInput(
    stars: $checkedConvert('stars', (v) => (v as num).toInt()),
    comment: $checkedConvert('comment', (v) => v as String?),
  );
  return val;
});

Map<String, dynamic> _$SharedLibraryRateInputToJson(
  SharedLibraryRateInput instance,
) => <String, dynamic>{'stars': instance.stars, 'comment': ?instance.comment};
