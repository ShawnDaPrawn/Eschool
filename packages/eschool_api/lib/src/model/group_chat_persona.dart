//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

enum GroupChatPersona {
  @JsonValue(r'staff')
  staff(r'staff'),
  @JsonValue(r'parent')
  parent(r'parent'),
  @JsonValue(r'student')
  student(r'student'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const GroupChatPersona(this.value);

  final String value;

  @override
  String toString() => value;
}
