//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'support_access.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportAccess {
  /// Returns a new [SupportAccess] instance.
  SupportAccess({
    required this.audiences,

    required this.canWrite,

    required this.today,
  });

  @JsonKey(name: r'audiences', required: true, includeIfNull: false)
  final List<String> audiences;

  @JsonKey(name: r'can_write', required: true, includeIfNull: false)
  final bool canWrite;

  @JsonKey(name: r'today', required: true, includeIfNull: false)
  final String today;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportAccess &&
          other.audiences == audiences &&
          other.canWrite == canWrite &&
          other.today == today;

  @override
  int get hashCode => audiences.hashCode + canWrite.hashCode + today.hashCode;

  factory SupportAccess.fromJson(Map<String, dynamic> json) =>
      _$SupportAccessFromJson(json);

  Map<String, dynamic> toJson() => _$SupportAccessToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
