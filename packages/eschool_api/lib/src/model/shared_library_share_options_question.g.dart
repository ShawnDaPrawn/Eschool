// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_share_options_question.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryShareOptionsQuestion _$SharedLibraryShareOptionsQuestionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SharedLibraryShareOptionsQuestion', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['kind', 'prompt']);
  final val = SharedLibraryShareOptionsQuestion(
    kind: $checkedConvert('kind', (v) => v as String),
    prompt: $checkedConvert('prompt', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$SharedLibraryShareOptionsQuestionToJson(
  SharedLibraryShareOptionsQuestion instance,
) => <String, dynamic>{'kind': instance.kind, 'prompt': instance.prompt};
