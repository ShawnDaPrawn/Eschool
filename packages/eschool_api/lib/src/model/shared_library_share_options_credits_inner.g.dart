// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_share_options_credits_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryShareOptionsCreditsInner
_$SharedLibraryShareOptionsCreditsInnerFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SharedLibraryShareOptionsCreditsInner', json, (
      $checkedConvert,
    ) {
      $checkKeys(json, requiredKeys: const ['value', 'label']);
      final val = SharedLibraryShareOptionsCreditsInner(
        value: $checkedConvert(
          'value',
          (v) => $enumDecode(
            _$SharedLibraryShareOptionsCreditsInnerValueEnumEnumMap,
            v,
            unknownValue: SharedLibraryShareOptionsCreditsInnerValueEnum
                .unknownDefaultOpenApi,
          ),
        ),
        label: $checkedConvert('label', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$SharedLibraryShareOptionsCreditsInnerToJson(
  SharedLibraryShareOptionsCreditsInner instance,
) => <String, dynamic>{
  'value':
      _$SharedLibraryShareOptionsCreditsInnerValueEnumEnumMap[instance.value]!,
  'label': instance.label,
};

const _$SharedLibraryShareOptionsCreditsInnerValueEnumEnumMap = {
  SharedLibraryShareOptionsCreditsInnerValueEnum.named: 'named',
  SharedLibraryShareOptionsCreditsInnerValueEnum.school: 'school',
  SharedLibraryShareOptionsCreditsInnerValueEnum.anonymous: 'anonymous',
  SharedLibraryShareOptionsCreditsInnerValueEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
