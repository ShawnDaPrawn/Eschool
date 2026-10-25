//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'consultation_option_count.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationOptionCount {
  /// Returns a new [ConsultationOptionCount] instance.
  ConsultationOptionCount({required this.optionId, required this.count});

  // minimum: 1
  @JsonKey(name: r'option_id', required: true, includeIfNull: false)
  final int optionId;

  // minimum: 0
  @JsonKey(name: r'count', required: true, includeIfNull: false)
  final int count;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationOptionCount &&
          other.optionId == optionId &&
          other.count == count;

  @override
  int get hashCode => optionId.hashCode + count.hashCode;

  factory ConsultationOptionCount.fromJson(Map<String, dynamic> json) =>
      _$ConsultationOptionCountFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationOptionCountToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
