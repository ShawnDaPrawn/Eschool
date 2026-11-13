// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'fixture_result_state.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FixtureResultState _$FixtureResultStateFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'FixtureResultState',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['state', 'host_score', 'guest_score'],
    );
    final val = FixtureResultState(
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$FixtureResultStateStateEnumEnumMap,
          v,
          unknownValue: FixtureResultStateStateEnum.unknownDefaultOpenApi,
        ),
      ),
      hostScore: $checkedConvert('host_score', (v) => (v as num?)?.toInt()),
      guestScore: $checkedConvert('guest_score', (v) => (v as num?)?.toInt()),
      winner: $checkedConvert(
        'winner',
        (v) => $enumDecodeNullable(
          _$FixtureResultStateWinnerEnumEnumMap,
          v,
          unknownValue: FixtureResultStateWinnerEnum.unknownDefaultOpenApi,
        ),
      ),
      myReport: $checkedConvert(
        'my_report',
        (v) => v == null
            ? null
            : FixtureScores.fromJson(v as Map<String, dynamic>),
      ),
      theirReport: $checkedConvert(
        'their_report',
        (v) => v == null
            ? null
            : FixtureScores.fromJson(v as Map<String, dynamic>),
      ),
      waitingFor: $checkedConvert(
        'waiting_for',
        (v) => $enumDecodeNullable(
          _$FixtureResultStateWaitingForEnumEnumMap,
          v,
          unknownValue: FixtureResultStateWaitingForEnum.unknownDefaultOpenApi,
        ),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'hostScore': 'host_score',
    'guestScore': 'guest_score',
    'myReport': 'my_report',
    'theirReport': 'their_report',
    'waitingFor': 'waiting_for',
  },
);

Map<String, dynamic> _$FixtureResultStateToJson(FixtureResultState instance) =>
    <String, dynamic>{
      'state': _$FixtureResultStateStateEnumEnumMap[instance.state]!,
      'host_score': instance.hostScore,
      'guest_score': instance.guestScore,
      'winner': ?_$FixtureResultStateWinnerEnumEnumMap[instance.winner],
      'my_report': ?instance.myReport?.toJson(),
      'their_report': ?instance.theirReport?.toJson(),
      'waiting_for':
          ?_$FixtureResultStateWaitingForEnumEnumMap[instance.waitingFor],
    };

const _$FixtureResultStateStateEnumEnumMap = {
  FixtureResultStateStateEnum.none: 'none',
  FixtureResultStateStateEnum.awaiting: 'awaiting',
  FixtureResultStateStateEnum.disputed: 'disputed',
  FixtureResultStateStateEnum.confirmed: 'confirmed',
  FixtureResultStateStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$FixtureResultStateWinnerEnumEnumMap = {
  FixtureResultStateWinnerEnum.host: 'host',
  FixtureResultStateWinnerEnum.guest: 'guest',
  FixtureResultStateWinnerEnum.draw: 'draw',
  FixtureResultStateWinnerEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$FixtureResultStateWaitingForEnumEnumMap = {
  FixtureResultStateWaitingForEnum.us: 'us',
  FixtureResultStateWaitingForEnum.them: 'them',
  FixtureResultStateWaitingForEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
