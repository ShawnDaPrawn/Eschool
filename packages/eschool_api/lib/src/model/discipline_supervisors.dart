//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/discipline_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'discipline_supervisors.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineSupervisors {
  /// Returns a new [DisciplineSupervisors] instance.
  DisciplineSupervisors({
    required this.page,

    required this.lastPage,

    required this.supervisors,
  });

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'supervisors', required: true, includeIfNull: false)
  final List<DisciplineChoice> supervisors;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineSupervisors &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.supervisors == supervisors;

  @override
  int get hashCode => page.hashCode + lastPage.hashCode + supervisors.hashCode;

  factory DisciplineSupervisors.fromJson(Map<String, dynamic> json) =>
      _$DisciplineSupervisorsFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineSupervisorsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
