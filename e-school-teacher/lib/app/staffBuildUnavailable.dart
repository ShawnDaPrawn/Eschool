import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/app/staffBuildIdentity.dart';
import 'package:flutter/material.dart';

/// No session, network fonts, Firebase or app repositories are initialized here.
class StaffBuildUnavailable extends StatelessWidget {
  const StaffBuildUnavailable({required this.identity, super.key});

  final StaffBuildIdentity identity;

  @override
  Widget build(BuildContext context) => MaterialApp(
    title: identity.displayName,
    theme: AppTheme.light(),
    debugShowCheckedModeBanner: false,
    home: Scaffold(
      appBar: AppBar(title: Text(identity.displayName)),
      body: SafeArea(
        child: Center(
          child: Padding(
            padding: EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: BoxConstraints(maxWidth: 560),
              child: const Text(
                'This app is not available yet. Please use the app provided by your school.',
              ),
            ),
          ),
        ),
      ),
    ),
  );
}
