//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/fixture_result_state.dart';
import 'package:eschool_api/src/model/fixture_team.dart';
import 'package:eschool_api/src/model/guardian_invitation_preview_learners_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'fixture_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FixtureSummary {
  /// Returns a new [FixtureSummary] instance.
  FixtureSummary({
    required this.id,

    required this.kind,

    required this.competition,

    required this.discipline,

    required this.onDate,

    required this.startsAt,

    required this.venue,

    required this.field,

    required this.hostIsHome,

    required this.status,

    required this.revision,

    required this.myRole,

    required this.myState,

    required this.open,

    required this.host,

    required this.guest,

    required this.result,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'kind', required: true, includeIfNull: false)
  final String kind;

  @JsonKey(name: r'competition', required: true, includeIfNull: true)
  final GuardianInvitationPreviewLearnersInner? competition;

  @JsonKey(name: r'discipline', required: true, includeIfNull: false)
  final String discipline;

  @JsonKey(name: r'on_date', required: true, includeIfNull: false)
  final String onDate;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: true)
  final String? startsAt;

  @JsonKey(name: r'venue', required: true, includeIfNull: false)
  final String venue;

  @JsonKey(name: r'field', required: true, includeIfNull: true)
  final String? field;

  @JsonKey(name: r'host_is_home', required: true, includeIfNull: false)
  final bool hostIsHome;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: FixtureSummaryStatusEnum.unknownDefaultOpenApi,
  )
  final FixtureSummaryStatusEnum status;

  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'my_role',
    required: true,
    includeIfNull: false,
    unknownEnumValue: FixtureSummaryMyRoleEnum.unknownDefaultOpenApi,
  )
  final FixtureSummaryMyRoleEnum myRole;

  @JsonKey(
    name: r'my_state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: FixtureSummaryMyStateEnum.unknownDefaultOpenApi,
  )
  final FixtureSummaryMyStateEnum myState;

  @JsonKey(name: r'open', required: true, includeIfNull: false)
  final bool open;

  @JsonKey(name: r'host', required: true, includeIfNull: false)
  final FixtureTeam host;

  @JsonKey(name: r'guest', required: true, includeIfNull: false)
  final FixtureTeam guest;

  @JsonKey(name: r'result', required: true, includeIfNull: false)
  final FixtureResultState result;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FixtureSummary &&
          other.id == id &&
          other.kind == kind &&
          other.competition == competition &&
          other.discipline == discipline &&
          other.onDate == onDate &&
          other.startsAt == startsAt &&
          other.venue == venue &&
          other.field == field &&
          other.hostIsHome == hostIsHome &&
          other.status == status &&
          other.revision == revision &&
          other.myRole == myRole &&
          other.myState == myState &&
          other.open == open &&
          other.host == host &&
          other.guest == guest &&
          other.result == result;

  @override
  int get hashCode =>
      id.hashCode +
      kind.hashCode +
      (competition == null ? 0 : competition.hashCode) +
      discipline.hashCode +
      onDate.hashCode +
      (startsAt == null ? 0 : startsAt.hashCode) +
      venue.hashCode +
      (field == null ? 0 : field.hashCode) +
      hostIsHome.hashCode +
      status.hashCode +
      revision.hashCode +
      myRole.hashCode +
      myState.hashCode +
      open.hashCode +
      host.hashCode +
      guest.hashCode +
      result.hashCode;

  factory FixtureSummary.fromJson(Map<String, dynamic> json) =>
      _$FixtureSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$FixtureSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum FixtureSummaryStatusEnum {
  @JsonValue(r'scheduled')
  scheduled(r'scheduled'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const FixtureSummaryStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum FixtureSummaryMyRoleEnum {
  @JsonValue(r'host')
  host(r'host'),
  @JsonValue(r'guest')
  guest(r'guest'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const FixtureSummaryMyRoleEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum FixtureSummaryMyStateEnum {
  @JsonValue(r'invited')
  invited(r'invited'),
  @JsonValue(r'accepted')
  accepted(r'accepted'),
  @JsonValue(r'declined')
  declined(r'declined'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const FixtureSummaryMyStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
