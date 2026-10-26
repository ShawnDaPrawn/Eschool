//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/family_health_value.dart';
import 'package:json_annotation/json_annotation.dart';

part 'family_health_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyHealthWrite {
  /// Returns a new [FamilyHealthWrite] instance.
  FamilyHealthWrite({required this.values});

  @JsonKey(name: r'values', required: true, includeIfNull: false)
  final Map<String, FamilyHealthValue> values;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyHealthWrite && other.values == values;

  @override
  int get hashCode => values.hashCode;

  factory FamilyHealthWrite.fromJson(Map<String, dynamic> json) =>
      _$FamilyHealthWriteFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyHealthWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
