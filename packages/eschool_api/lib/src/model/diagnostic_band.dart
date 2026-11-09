//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

/// How a topic came out in the starting check, from its own answers only (staff only).
enum DiagnosticBand {
  @JsonValue(r'secure')
  secure(r'secure'),
  @JsonValue(r'developing')
  developing(r'developing'),
  @JsonValue(r'gap')
  gap(r'gap'),
  @JsonValue(r'not_reached')
  notReached(r'not_reached'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DiagnosticBand(this.value);

  final String value;

  @override
  String toString() => value;
}
