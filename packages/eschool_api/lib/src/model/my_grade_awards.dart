//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/my_grade_award_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'my_grade_awards.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MyGradeAwards {
  /// Returns a new [MyGradeAwards] instance.
  MyGradeAwards({required this.available, required this.items});

  @JsonKey(name: r'available', required: true, includeIfNull: false)
  final bool available;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<MyGradeAwardChoice> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MyGradeAwards &&
          other.available == available &&
          other.items == items;

  @override
  int get hashCode => available.hashCode + items.hashCode;

  factory MyGradeAwards.fromJson(Map<String, dynamic> json) =>
      _$MyGradeAwardsFromJson(json);

  Map<String, dynamic> toJson() => _$MyGradeAwardsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
