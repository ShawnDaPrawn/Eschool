//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/discipline_optional_framework.dart';
import 'package:json_annotation/json_annotation.dart';

part 'discipline_framework_view.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineFrameworkView {
  /// Returns a new [DisciplineFrameworkView] instance.
  DisciplineFrameworkView({
    required this.sessionYearId,

    required this.framework,
  });

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'framework', required: true, includeIfNull: true)
  final DisciplineOptionalFramework? framework;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineFrameworkView &&
          other.sessionYearId == sessionYearId &&
          other.framework == framework;

  @override
  int get hashCode =>
      sessionYearId.hashCode + (framework == null ? 0 : framework.hashCode);

  factory DisciplineFrameworkView.fromJson(Map<String, dynamic> json) =>
      _$DisciplineFrameworkViewFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineFrameworkViewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
