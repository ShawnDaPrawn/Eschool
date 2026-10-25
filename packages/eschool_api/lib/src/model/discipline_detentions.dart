//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/discipline_detention.dart';
import 'package:json_annotation/json_annotation.dart';

part 'discipline_detentions.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineDetentions {
  /// Returns a new [DisciplineDetentions] instance.
  DisciplineDetentions({
    required this.sessionYearId,

    required this.page,

    required this.lastPage,

    required this.sessions,
  });

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'sessions', required: true, includeIfNull: false)
  final List<DisciplineDetention> sessions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineDetentions &&
          other.sessionYearId == sessionYearId &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.sessions == sessions;

  @override
  int get hashCode =>
      sessionYearId.hashCode +
      page.hashCode +
      lastPage.hashCode +
      sessions.hashCode;

  factory DisciplineDetentions.fromJson(Map<String, dynamic> json) =>
      _$DisciplineDetentionsFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineDetentionsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
