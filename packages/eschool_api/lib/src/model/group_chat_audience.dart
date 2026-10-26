//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

enum GroupChatAudience {
  @JsonValue(r'learners')
  learners(r'learners'),
  @JsonValue(r'guardians')
  guardians(r'guardians'),
  @JsonValue(r'staff')
  staff(r'staff'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const GroupChatAudience(this.value);

  final String value;

  @override
  String toString() => value;
}
