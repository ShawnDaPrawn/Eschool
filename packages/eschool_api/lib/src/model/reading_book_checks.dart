//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'reading_book_checks.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReadingBookChecks {
  /// Returns a new [ReadingBookChecks] instance.
  ReadingBookChecks({
    required this.total,

    required this.open,

    required this.answered,
  });

  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  @JsonKey(name: r'open', required: true, includeIfNull: false)
  final int open;

  @JsonKey(name: r'answered', required: true, includeIfNull: false)
  final int answered;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReadingBookChecks &&
          other.total == total &&
          other.open == open &&
          other.answered == answered;

  @override
  int get hashCode => total.hashCode + open.hashCode + answered.hashCode;

  factory ReadingBookChecks.fromJson(Map<String, dynamic> json) =>
      _$ReadingBookChecksFromJson(json);

  Map<String, dynamic> toJson() => _$ReadingBookChecksToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
