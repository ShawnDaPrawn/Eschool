//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'reenrolment_contract_list_items_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReenrolmentContractListItemsInner {
  /// Returns a new [ReenrolmentContractListItemsInner] instance.
  ReenrolmentContractListItemsInner({
    required this.id,

    required this.sessionYearId,

    required this.sessionYear,

    required this.version,

    required this.title,

    required this.textHash,

    required this.acceptanceRule,

    required this.respondBy,

    required this.state,

    required this.revision,

    required this.publishedAt,

    required this.confirmed,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'session_year', required: true, includeIfNull: false)
  final String sessionYear;

  // minimum: 1
  @JsonKey(name: r'version', required: true, includeIfNull: false)
  final int version;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'text_hash', required: true, includeIfNull: false)
  final String textHash;

  @JsonKey(name: r'acceptance_rule', required: true, includeIfNull: false)
  final String acceptanceRule;

  @JsonKey(name: r'respond_by', required: true, includeIfNull: false)
  final DateTime respondBy;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        ReenrolmentContractListItemsInnerStateEnum.unknownDefaultOpenApi,
  )
  final ReenrolmentContractListItemsInnerStateEnum state;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'published_at', required: true, includeIfNull: false)
  final DateTime publishedAt;

  @JsonKey(name: r'confirmed', required: true, includeIfNull: false)
  final int confirmed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReenrolmentContractListItemsInner &&
          other.id == id &&
          other.sessionYearId == sessionYearId &&
          other.sessionYear == sessionYear &&
          other.version == version &&
          other.title == title &&
          other.textHash == textHash &&
          other.acceptanceRule == acceptanceRule &&
          other.respondBy == respondBy &&
          other.state == state &&
          other.revision == revision &&
          other.publishedAt == publishedAt &&
          other.confirmed == confirmed;

  @override
  int get hashCode =>
      id.hashCode +
      sessionYearId.hashCode +
      sessionYear.hashCode +
      version.hashCode +
      title.hashCode +
      textHash.hashCode +
      acceptanceRule.hashCode +
      respondBy.hashCode +
      state.hashCode +
      revision.hashCode +
      publishedAt.hashCode +
      confirmed.hashCode;

  factory ReenrolmentContractListItemsInner.fromJson(
    Map<String, dynamic> json,
  ) => _$ReenrolmentContractListItemsInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$ReenrolmentContractListItemsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ReenrolmentContractListItemsInnerStateEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'superseded')
  superseded(r'superseded'),
  @JsonValue(r'withdrawn')
  withdrawn(r'withdrawn'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ReenrolmentContractListItemsInnerStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
