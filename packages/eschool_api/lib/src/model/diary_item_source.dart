//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

enum DiaryItemSource {
  @JsonValue(r'homework')
  homework(r'homework'),
  @JsonValue(r'holiday')
  holiday(r'holiday'),
  @JsonValue(r'event')
  event(r'event'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DiaryItemSource(this.value);

  final String value;

  @override
  String toString() => value;
}
