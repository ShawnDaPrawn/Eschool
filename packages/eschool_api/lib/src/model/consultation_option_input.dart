//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'consultation_option_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationOptionInput {
  /// Returns a new [ConsultationOptionInput] instance.
  ConsultationOptionInput({required this.label, this.statement});

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  @JsonKey(name: r'statement', required: false, includeIfNull: false)
  final String? statement;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationOptionInput &&
          other.label == label &&
          other.statement == statement;

  @override
  int get hashCode =>
      label.hashCode + (statement == null ? 0 : statement.hashCode);

  factory ConsultationOptionInput.fromJson(Map<String, dynamic> json) =>
      _$ConsultationOptionInputFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationOptionInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
