import 'dart:async';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import '../discipline/discipline_repository.dart';
import '../discipline/discipline_screen.dart';
import '../messages/message_repository.dart';
import '../messages/message_editor_screen.dart';
import '../recognition/recognition_repository.dart';
import '../recognition/recognition_screen.dart';
import '../risk/risk_repository.dart';
import '../risk/risk_screen.dart';
import '../registers/register_repository.dart';
import '../registers/register_screen.dart';
import '../registers/register_store.dart';
import 'my_grade_repository.dart';
import 'my_grade_screen.dart';
import 'my_grade_private_action.dart';

/// Shared action composition for classic Teacher and the separately scoped shell.
class MyGradeClientScreen extends StatefulWidget {
  const MyGradeClientScreen({
    required this.client,
    required this.schoolId,
    required this.readerId,
    required this.contextActive,
    this.contextChanges = const Stream.empty(),
    this.owner,
    super.key,
  });
  final ApiClient client;
  final int schoolId, readerId;
  final bool Function() contextActive;
  final Stream<void> contextChanges;
  final Listenable? owner;
  @override
  State<MyGradeClientScreen> createState() => _MyGradeClientScreenState();
}

class _MyGradeClientScreenState extends State<MyGradeClientScreen> {
  late final MyGradeRepository _repository = ApiMyGradeRepository(
    widget.client,
    schoolId: widget.schoolId,
    readerId: widget.readerId,
    contextActive: () => mounted && widget.contextActive(),
  );
  bool get _active => mounted && _repository.isActive;
  void _guard() {
    if (!_active) {
      throw ApiException(
        'Reopen My grade to check your access.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
  }

  Future<void> _open(int grade, Widget child) async {
    _guard();
    final access = await _repository.access();
    _guard();
    if (!access.grades.any((row) => row.id == grade)) {
      throw ApiException(
        'Reopen My grade to check your access.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
    if (!mounted) return;
    await Navigator.of(context).push<void>(
      AdaptivePageRoute(
        contentColumn: false,
        builder: (_) => MyGradePrivateAction(
          current: () => mounted && _repository.isActive,
          changes: widget.contextChanges,
          owner: widget.owner,
          reauthorize: () async {
            if (!_active) return false;
            final fresh = await _repository.access();
            return _active &&
                fresh.sessionYearId == access.sessionYearId &&
                fresh.grades.any((row) => row.id == grade);
          },
          child: child,
        ),
      ),
    );
  }

  Future<void> _register(MyGradeLearner learner, int grade) async {
    _guard();
    final access = await _repository.access();
    _guard();
    if (!access.grades.any((row) => row.id == grade)) {
      throw ApiException(
        'Reopen My grade to check your access.',
        statusCode: 403,
      );
    }
    final repository = ApiRegisterRepository(
      widget.client,
      schoolId: widget.schoolId,
      accountId: widget.readerId,
      contextActive: () => mounted && _repository.isActive,
    );
    final matrix = learner.registerId == null
        ? await repository.open(
            AcademicRegisterTargetWrite(
              sessionYearId: access.sessionYearId,
              onDate: access.today,
              classSectionId: learner.classSectionId,
            ),
          )
        : await repository.matrix(learner.registerId!);
    _guard();
    if (matrix.sessionYearId != access.sessionYearId ||
        matrix.classSectionId != learner.classSectionId ||
        matrix.onDate != access.today) {
      throw ApiException(
        'Reopen My grade to check your access.',
        statusCode: 403,
      );
    }
    // Reuse the encrypted, owner-locked queue: an unknown sync outcome keeps
    // its original UUID when this private register is reopened after refresh.
    final store = await SecureRegisterStore.open(widget.readerId);
    _guard();
    await _open(
      grade,
      RegisterScreen.withRepository(
        repository,
        store,
        matrix.registerId,
        initial: matrix,
      ),
    );
  }

  @override
  Widget build(BuildContext context) => MyGradeScreen(
    repository: _repository,
    onRegister: _register,
    onDiscipline: (record, grade) => _open(
      grade,
      SchoolDisciplineScreen(
        initialRecord: record.id,
        canRead: () => mounted && _repository.isActive,
        repository: ApiDisciplineRepository(
          widget.client,
          schoolId: widget.schoolId,
          readerId: widget.readerId,
          contextActive: () => mounted && _repository.isActive,
        ),
      ),
    ),
    onMessage: (grade) => _open(
      grade,
      SchoolMessageEditorScreen(
        initialGradeId: grade,
        repository: ApiSchoolMessageRepository(
          widget.client,
          schoolId: widget.schoolId,
          readerId: widget.readerId,
          contextActive: () => mounted && _repository.isActive,
        ),
      ),
    ),
    onRisk: (grade) => _open(
      grade,
      RiskInterventionsScreen(
        canRead: () => mounted && _repository.isActive,
        repository: ApiRiskRepository(
          widget.client,
          schoolId: widget.schoolId,
          readerId: widget.readerId,
          contextActive: () => mounted && _repository.isActive,
        ),
      ),
    ),
    onAwards: (grade) => _open(
      grade,
      RecognitionScreen(
        modules: const ['awards'],
        repository: ApiRecognitionRepository(
          widget.client,
          schoolId: widget.schoolId,
          accountId: widget.readerId,
          contextActive: () => mounted && _repository.isActive,
        ),
      ),
    ),
  );
}
