//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

enum SignInProviderName {
  @JsonValue(r'google')
  google(r'google'),
  @JsonValue(r'microsoft')
  microsoft(r'microsoft'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SignInProviderName(this.value);

  final String value;

  @override
  String toString() => value;
}
